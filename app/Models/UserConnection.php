<?php

namespace App\Models;

use App\Models\UserConnectionMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function metas(): HasMany
    {
        return $this->hasMany(UserConnectionMeta::class);
    }

    public function setMeta(string $key, mixed $value): void
    {
        $this->metas()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        $metaVal = $this->metas->where('key', '=', $key)->first();

        return $metaVal ? json_decode($metaVal->value) : $default;
    }

    public function hasMeta(string $key): bool
    {
        return $this->metas->where('key', '=', $key)->exists();
    }
}

