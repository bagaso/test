<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserCreditLog extends Model
{
    public function user_related()
    {
        return $this->belongsTo('App\User', 'user_id_related')->select('id', 'username');
    }

    public function getUserFromAttribute() {
        if(!is_null($this->user_related)) {
            return $this->user_related->username;
        }
        return '###';
    }

    protected $hidden = [
        'user_id', 'user_id_related', 'user_related',
    ];

    protected $appends = [
        'user_from',
    ];

}
