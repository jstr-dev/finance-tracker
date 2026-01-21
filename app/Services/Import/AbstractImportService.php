<?php

namespace App\Services\Import;

use App\Exceptions\InvalidHeadersException;
use App\Exceptions\InvalidRowException;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Storage;

/**
 * Used for importing transaction history via .csv files. 
 */
abstract class AbstractImportService
{   
    private const CHUNK_SIZE = 100;

    private string $currency = 'GBP';
    private $readStream = null;
    private array $headers = [];
    private int $headerCount = 0;

    abstract protected function getType(): string;
    abstract protected function getRequiredCSVHeaders(): array;
    abstract protected function getRowTransactionID(array $row): string;

    private function checkHeadersAreValid(array $csvHeaders): bool
    {
        $required = $this->normaliseHeaders($this->getRequiredCSVHeaders());

        return empty(array_diff($required, $csvHeaders));
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getCurrency(): string
    {
        return $this->currency;
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
            $this->readStream = Storage::readStream($path);
            $this->headers = $this->normaliseHeaders(fgetcsv($this->readStream));
            $this->headerCount = count($this->headers);

            assert($this->checkHeadersAreValid($this->headers), new InvalidHeadersException());

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
                $this->processChunk($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            $this->processChunk($batch);
        }
    }

    private function formatRowWithHeaders(array $row, array $headers): array
    {
        return array_combine($headers, $row);
    }

    private function processChunk(array $chunk): void
    {
    }
}