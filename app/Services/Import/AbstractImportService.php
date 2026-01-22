<?php

namespace App\Services\Import;

use App\Exceptions\InvalidHeadersException;
use App\Exceptions\InvalidRowException;
use App\Jobs\ProcessCSVImport;
use App\Models\Import;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\TransactionNormalizationService;
use Exception;
use Illuminate\Support\Facades\Storage;

/**
 * Used for importing transaction history via .csv files. 
 */
abstract class AbstractImportService
{   
    private const CHUNK_SIZE = 100;

    private string $currency = 'GBP';
    private string $disk = 'local';
    private $readStream = null;
    private array $headers = [];
    private int $headerCount = 0;
    private ?Import $import = null;

    abstract protected function getType(): string;
    abstract protected function getRequiredCSVHeaders(): array;
    abstract protected function getRowTransactionID(array $row): string;
    abstract protected function formatRowForImport(array $row): array;

    /**
     * Start an import. Creates Import record and either processes synchronously or dispatches a job.
     */
    public function startImport(User $user, string $path, bool $async = false): Import
    {
        $import = Import::create([
            'user_id' => $user->id,
            'type' => $this->getType(),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        if ($async) {
            ProcessCSVImport::dispatch(
                $import->id,
                $user->id,
                $path,
                static::class
            );
        } else {
            $this->processImport($import, $user, $path);
        }

        return $import;
    }

    /**
     * Process the actual import. Called either synchronously or from a job.
     */
    public function processImport(Import $import, User $user, string $path): void
    {
        $this->import = $import;

        try {
            $this->import($user, $path);

            $import->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (Exception $e) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    private function checkHeadersAreValid(array $csvHeaders): bool
    {
        $required = $this->normaliseHeaders($this->getRequiredCSVHeaders());

        return empty(array_diff($required, $csvHeaders));
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setImportId(int $importId): void
    {
        $this->importId = $importId;
    }

    public function setDisk(string $disk): void
    {
        $this->disk = $disk;
    }

    private function normaliseHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $trimmed = trim($header);
            $lowercased = strtolower($trimmed);
            $normalized = preg_replace('/\s+/', ' ', $lowercased);

            return $normalized;
        }, $headers);
    }

    public function import(User $user, string $path): void 
    {
        try {
            $this->readStream = Storage::disk($this->disk)->readStream($path);
            $this->headers = $this->normaliseHeaders(fgetcsv($this->readStream));
            $this->headerCount = count($this->headers);

            if (!$this->checkHeadersAreValid($this->headers)) {
                throw new InvalidHeadersException();
            }

            $this->importTransactions($user);
        } catch (Exception $e) {
            if (isset($this->readStream) && is_resource($this->readStream)) {
                fclose($this->readStream);
            }

            throw $e;
        }
    }

    private function importTransactions(User $user): void
    {
        $batch = []; 

        while ($row = fgetcsv($this->readStream)) {
            if (count($row) !== $this->headerCount) {
                throw new InvalidRowException("Row count (" . count($row) . ") does not match header count ({$this->headerCount}).");
            }

            $batch[] = $this->formatRowWithHeaders($row, $this->headers);

            if (count($batch) === self::CHUNK_SIZE) {
                $this->processChunk($user, $batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            $this->processChunk($user, $batch);
        }
    }

    private function formatRowWithHeaders(array $row, array $headers): array
    {
        $trimmedRow = array_map('trim', $row);
        return array_combine($headers, $trimmedRow);
    }

    private function processChunk(User $user, array $chunk): void
    {
        $normalizationService = app(TransactionNormalizationService::class);

        $rawMerchants = [];
        $rawCategories = [];

        foreach ($chunk as $row) {
            $formatted = $this->formatRowForImport($row);

            if (!empty($formatted['payee'])) {
                $rawMerchants[] = $formatted['payee'];
            }

            if ($this instanceof HasCategory) {
                $category = $this->extractCategory($row);
                if ($category) {
                    $rawCategories[] = $category;
                }
            }
        }

        $rawMerchants = array_unique($rawMerchants);
        $rawCategories = array_unique($rawCategories);

        $normalizedMerchants = $normalizationService->normalizeMerchants($rawMerchants);
        $normalizedCategories = !empty($rawCategories)
            ? $normalizationService->normalizeCategories($rawCategories)
            : [];

        $transactions = [];
        foreach ($chunk as $row) {
            $formatted = $this->formatRowForImport($row);
            $transactionId = $this->getRowTransactionID($row);

            $rawMerchant = $formatted['payee'] ?? null;
            $rawCategory = ($this instanceof HasCategory) ? $this->extractCategory($row) : null;

            $transaction = array_merge($formatted, [
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'merchant' => $rawMerchant ? ($normalizedMerchants[$rawMerchant] ?? $rawMerchant) : null,
                'category' => $rawCategory ? ($normalizedCategories[$rawCategory] ?? $rawCategory) : null,
                'currency' => $this->currency,
                'import_id' => $this->import?->id,
                'imported_at' => now(),
                'payload' => json_encode($row),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $transactions[] = $transaction;
        }

        UserTransaction::upsert(
            $transactions,
            ['transaction_id'],
            ['merchant', 'category', 'amount', 'transaction_date', 'updated_at', 'payload']
        );
    }
}