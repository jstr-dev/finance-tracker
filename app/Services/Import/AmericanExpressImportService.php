<?php

namespace App\Services\Import;

class AmericanExpressImportService extends AbstractImportService implements HasCategory
{
    public function getType(): string
    {
        return 'amex';
    }

    public function getRequiredCSVHeaders(): array
    {
        return [
            'Date',
            'Description',
            'Amount',
            'Reference'
        ];
    }

    public function getRowTransactionID(array $row): string
    {
        $transactionId = $row['reference'];
        $transactionId = str_replace(' ', '', $transactionId);
        $transactionId = str_replace('\'', '', $transactionId);

        return $transactionId;
    }

    public function extractCategory(array $row): ?string
    {
        return $row['category'] ?? null;
    }

    public function formatRowForImport(array $row): array
    {
        $date = $row['date'];
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $matches)) {
            $date = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        return [
            'transaction_date' => $date,
            'payee' => $row['appears on your statement as'] ?? $row['description'],
            'amount' => $row['amount'],
            'description' => $row['extended details'] ?? null,
            'city' => $row['town/city'] ?? null,
            'postcode' => $row['postcode'] ?? null,
            'country' => $row['country'] ?? null,
        ];
    }
}