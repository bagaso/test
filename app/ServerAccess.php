<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServerAccess extends Model
{
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];
}
