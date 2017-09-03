<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    protected $casts = [
        'user_package' => 'array',
        'is_active' => 'boolean',
        'vpn_login' => 'boolean',
        'ss_login' => 'boolean',
    ];

    public function vpn_server()
    {
        return $this->belongsToMany('App\VpnServer');
    }
}
