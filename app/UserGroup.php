<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $casts = [
        'lang' => 'array',
    ];
}
