<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServerAccess extends Model
{
    protected $casts = [
        'config' => 'json',
    ];
}
