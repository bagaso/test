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
        'allowed_userpackage' => 'array',
        'limit_bandwidth' => 'boolean',
    ];

    public function setServerNameAttribute($value)
    {
        $this->attributes['server_name'] = strtoupper($value);
    }

    public function setServerDomainAttribute($value)
    {
        $this->attributes['server_domain'] = strtoupper($value);
    }
    

    public function online_users()
    {
        return $this->hasMany('App\OnlineUser');
    }

    public function online_counts()
    {
        return $this->online_users->count();
    }

    public function users()
    {
        return $this->hasManyThrough(
            'App\User', 'App\OnlineUser',
            'vpn_server_id', 'id', 'id'
        );
    }

    public function server_access()
    {
        return $this->belongsTo('App\ServerAccess');
    }

    public function user_packages()
    {
        return $this->belongsToMany('App\UserPackage');
    }

    public function user_access()
    {
        return $this->belongsToMany('App\User');
    }
}
