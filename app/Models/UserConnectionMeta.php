<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConnectionMeta extends Model
{
    protected $table = 'user_connection_meta';
    protected $fillable = ['key', 'value'];
}
