<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Updates extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'content', 'pinned',
    ];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    public function getCreatedAtAttribute($value) {
        $dt = Carbon::parse($value);
        if ($dt->diffInDays(Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }
}
