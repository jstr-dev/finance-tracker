<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public function getAccessAttribute()
    {
        if (!auth()) {
            return null;
        }

        return auth()->user()->can($this->getKey());
    }
}
