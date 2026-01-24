<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
    ];

    // Account types
    public const ACCOUNT_TYPE_CREDIT = 'credit';
    public const ACCOUNT_TYPE_DEBIT = 'debit';
    public const ACCOUNT_TYPE_INVESTMENT = 'investment';
    public const ACCOUNT_TYPE_CASH = 'cash';

    // Transaction types
    public const TRANSACTION_TYPE_PURCHASE = 'purchase';
    public const TRANSACTION_TYPE_PAYMENT = 'payment';

    // Provider codes
    public const CODE_AMEX = 'amex';
    public const CODE_MONZO = 'monzo';
    public const CODE_TRADING212 = 'trading212';

    /**
     * Get all transactions for this provider.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(UserTransaction::class);
    }
}
