<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminTransferLog extends Model
{
    public function userfrom1()
    {
        return $this->belongsTo('App\User', 'user_id_from')->select('id', 'username');
    }

    public function userto1()
    {
        return $this->belongsTo('App\User', 'user_id_to')->select('id', 'username');
    }

    public function getUserFromAttribute() {
        if(!is_null($this->userfrom1)) {
            return $this->userfrom1->username;
        }
        return '###';
    }

    public function getUserToAttribute() {
        if($this->user_id_to == 0) {
            return '---';
        }
        if(!is_null($this->userto1)) {
            return $this->userto1->username;
        }
        return '###';
    }

    protected $hidden = [
        'user_id_from', 'user_id_to', 'userfrom1', 'userto1',
    ];

    protected $appends = [
        'user_from', 'user_to',
    ];
}
