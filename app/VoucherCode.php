<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VoucherCode extends Model
{
    protected $fillable = [
        'code', 'duration',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
