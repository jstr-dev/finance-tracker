<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserConnection extends Model
{
    protected $fillable = ['user_id', 'access_token'];
    protected $hidden = ['access_token', 'refresh_token', 'access_expires_at', 'refresh_expires_at'];
    protected ?string $decryptedToken = null;

    public function scopeTrading212(Builder $query)
    {
        return $query->where('connection_type', 'trading212');
    }

    public function getAccessToken()
    {
        if ($this->decryptedToken) {
            return $this->decryptedToken;
        }

        $this->decryptedToken = decrypt($this->access_token);

        return $this->decryptedToken;
    }
}

