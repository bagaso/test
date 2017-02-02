<?php

namespace App;

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
        'username', 'server', 'byte_sent', 'byte_received',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
