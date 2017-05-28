<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VpnHistory extends Model
{
    public $timestamps = false;

    public function user_related()
    {
        return $this->belongsTo('App\User', 'user_id')->select('id', 'username');
    }

    public function getUserFromAttribute() {
        if(!is_null($this->user_related)) {
            return $this->user_related->username;
        }
        return '###';
    }

    protected $hidden = [
        'user_id', 'user_related',
    ];

    public function sizeformat($bytesize)
    {
        $i=0;
        while(abs($bytesize) >= 1024) {
            $bytesize=$bytesize/1024;
            $i++;
            if($i==4) break;
        }

        $units = array("Bytes","KB","MB","GB","TB");
        $newsize=round($bytesize,2);
        return("$newsize $units[$i]");
    }

    public function getByteSentAttribute($value) {
        return $this->sizeformat($value);
    }

    public function getByteReceivedAttribute($value) {
        return $this->sizeformat($value);
    }

    protected $appends = [
        'user_from',
    ];
}
