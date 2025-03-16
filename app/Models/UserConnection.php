<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserConnection extends Model
{
    public function scopeTrading212(Builder $query)
    {
        return $query->where('connection_type', 'trading212');
    }
}

