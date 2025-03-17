<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserConnection extends Model
{
    protected $fillable = ['user_id', 'access_token'];
    protected $hidden = ['access_token', 'refresh_token', 'access_expires_at', 'refresh_expires_at'];

    public function scopeTrading212(Builder $query)
    {
        return $query->where('connection_type', 'trading212');
    }
}

