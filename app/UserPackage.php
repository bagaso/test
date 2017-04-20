<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    protected $casts = [
        'user_package' => 'array',
    ];
}
