<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransaction extends Model
{
    protected $fillable = [
        'user_id',
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
}
