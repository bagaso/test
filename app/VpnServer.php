<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VpnServer extends Model
{
    protected $fillable = [
        'server_ip', 'server_domain', 'server_name', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function online_users()
    {
        return $this->hasMany('App\OnlineUser');
    }

    public function users()
    {
        return $this->hasManyThrough(
            'App\User', 'App\OnlineUser',
            'vpn_server_id', 'id', 'id'
        );
    }
}
