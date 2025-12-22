<?php

namespace App\Services\Import;

class AmericanExpressImportService extends AbstractImportService
{
    protected function getType(): string
    {
        return 'amex';
    }

    protected function getRequiredCSVHeaders(): array
    {
        return [
            'Date',
            'Description',
            'Amount',
            'Reference'
        ];
    }

    protected function getRowTransactionID(array $row): string
    {
        $transactionId = $row['Reference']; 
        $transactionId = str_replace(' ', '', $transactionId);
        $transactionId = str_replace('\'', '', $transactionId);

        return $transactionId;
    }
       
    protected function formatRowForImport(array $row): array
    {
        return [
            'transaction_date' => $row['Date'],
            'merchant' => $row['Description'],
            'amount' => $row['Amount'],
        ];
    }
}