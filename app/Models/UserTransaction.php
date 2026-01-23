<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'account_type',
        'transaction_type',
        'transaction_id',
        'payee',
        'merchant',
        'category',
        'description',
        'transaction_date',
        'amount',
        'currency',
        'postcode',
        'country',
        'city',
        'import_id',
        'imported_at',
        'payload',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'imported_at' => 'datetime',
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Scope for credit account transactions.
     */
    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('account_type', Provider::ACCOUNT_TYPE_CREDIT);
    }

    /**
     * Scope for debit account transactions.
     */
    public function scopeDebit(Builder $query): Builder
    {
        return $query->where('account_type', Provider::ACCOUNT_TYPE_DEBIT);
    }

    /**
     * Scope for purchase transactions.
     */
    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('transaction_type', Provider::TRANSACTION_TYPE_PURCHASE);
    }

    /**
     * Scope for payment transactions.
     */
    public function scopePayments(Builder $query): Builder
    {
        return $query->where('transaction_type', Provider::TRANSACTION_TYPE_PAYMENT);
    }

    /**
     * Check if transaction is from a credit account.
     */
    public function isCredit(): bool
    {
        return $this->account_type === Provider::ACCOUNT_TYPE_CREDIT;
    }

    /**
     * Check if transaction is from a debit account.
     */
    public function isDebit(): bool
    {
        return $this->account_type === Provider::ACCOUNT_TYPE_DEBIT;
    }

    /**
     * Check if transaction is a purchase.
     */
    public function isPurchase(): bool
    {
        return $this->transaction_type === Provider::TRANSACTION_TYPE_PURCHASE;
    }

    /**
     * Check if transaction is a payment.
     */
    public function isPayment(): bool
    {
        return $this->transaction_type === Provider::TRANSACTION_TYPE_PAYMENT;
    }
}
