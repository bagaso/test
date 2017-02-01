<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OnlineUser extends Model
{
    protected $fillable = ['username', 'server', 'byte_sent', 'byte_received', 'counter'];
}
