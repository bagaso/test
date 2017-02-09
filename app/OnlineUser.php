<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OnlineUser extends Model
{
    /**
     * primaryKey
     *
     * @var integer
     * @access protected
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'vpn_server_id', 'byte_sent', 'byte_received',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function vpnserver()
    {
        return $this->belongsTo('App\VpnServer', 'vpn_server_id', 'id');
    }

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

    public function getCreatedAtAttribute($value) {
        $dt = Carbon::parse($value);
        if ($dt->diffInDays(Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }
}
