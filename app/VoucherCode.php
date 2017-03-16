<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VoucherCode extends Model
{
    protected $fillable = [
        'code', 'duration',
    ];

    public function user_applied()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function user_created()
    {
        return $this->belongsTo('App\User', 'created_user_id');
    }

    protected $operators = [
        'equal' => '=',
        'not_equal' => '<>',
        'less_than' => '<',
        'greater_than' => '>',
        'less_than_or_equal_to' => '<=',
        'greater_than_or_equal_to' => '>=',
        'in' => 'IN',
        'like' => 'LIKE'
    ];

    public function scopeSearchPaginateAndOrder($query, $request)
    {
        if(is_null($request->column) || trim($request->column) == '') {
            $request->column = 'code';
        }
        return $query->orderBy($request->column, $request->direction)
            ->where(function($query) use ($request) {
                if($request->has('search_input')) {
                    if($request->search_operator == 'in') {
                        $query->whereIn($request->search_column, array_map('trim', explode(',', $request->search_input)));
                    } else if($request->search_operator == 'like') {
                        $query->where($request->search_column, 'LIKE', '%'.trim($request->search_input).'%');
                    } else {
                        $query->where($request->search_column, $this->operators[$request->search_operator], trim($request->search_input));
                    }
                }
            })->paginate($request->per_page);
    }

    public function getDurationAttribute($value) {
        $current = Carbon::now();
        if ($current->copy()->addSeconds($value)->diffInDays($current) > 1)
            return $current->copy()->addSeconds($value)->diffInDays($current) . ' Days';
        else
            return $current->copy()->addSeconds($value)->diffForHumans(null, true);
        // return $current->addSeconds($value)->diffForHumans(null, true);
    }

    public function getCreatedAtAttribute($value) {
        $dt = \Carbon\Carbon::parse($value);
        if ($dt->diffInDays(\Carbon\Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }

    public function getUpdatedAtAttribute($value) {
        $dt = \Carbon\Carbon::parse($value);
        if(is_null($this->user_id)) {
            return '-';
        }
        if ($dt->diffInDays(\Carbon\Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }

    public function getAppliedToAttribute() {
        if(!is_null($this->user_applied)) {
            return $this->user_applied->username;
        }
        if(!is_null($this->user_id) && is_null($this->user_applied)) {
            return '###';
        }
        return '-';
    }

    public function getCreatedByAttribute() {
        if(!is_null($this->user_created)) {
            return $this->user_created->username;
        }
        return '###';
    }

    protected $appends = [
        'applied_to', 'created_by',
    ];
}
