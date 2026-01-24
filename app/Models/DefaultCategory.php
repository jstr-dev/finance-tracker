<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];
}
