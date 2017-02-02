<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VpnServer extends Model
{
    /**
     * primaryKey
     *
     * @var integer
     * @access protected
     */
    protected $primaryKey = 'server_ip';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'server_domain', 'server_name', 'server_ip', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
