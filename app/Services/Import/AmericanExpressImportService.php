<?php

namespace App\Services\Import;

use App\Models\Provider;

class AmericanExpressImportService extends AbstractImportService implements HasCategory
{
    /**
     * Patterns to detect payment transactions (case-insensitive).
     */
    private const PAYMENT_PATTERNS = [
        'PAYMENT.*THANK\s*YOU',
        'DIRECT\s*DEBIT',
        'PAYMENT\s*RECEIVED',
        'AUTOPAY',
        'AUTOMATIC\s*PAYMENT',
    ];

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

    protected function getProviderId(): int
    {
        return Provider::where('code', Provider::CODE_AMEX)->value('id');
    }

    protected function getAccountType(): string
    {
        return Provider::ACCOUNT_TYPE_CREDIT;
    }

    protected function isPayment(array $row): bool
    {
        $description = $row['description'] ?? '';
        $payee = $row['appears on your statement as'] ?? '';
        $searchText = $description . ' ' . $payee;

        foreach (self::PAYMENT_PATTERNS as $pattern) {
            if (@preg_match('~' . $pattern . '~i', $searchText)) {
                return true;
            }
        }

        return false;
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