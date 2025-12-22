<?php

namespace App\Services\Import;

use App\Exceptions\InvalidHeadersException;
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

    abstract protected function getType(): string;
    abstract protected function getRequiredCSVHeaders(): array;
    abstract protected function getRowTransactionID(array $row): string;

    private function checkHeadersAreValid(array $csvHeaders): bool
    {
        $requiredHeaders = $this->getRequiredCSVHeaders();

        return count(array_diff($requiredHeaders, $csvHeaders)) === 0;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function import(User $user, string $path): void 
    {
        try {
            $this->readStream = Storage::readStream($path);
            $this->headers = fgetcsv($this->readStream);

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
            $batch[] = $row;

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