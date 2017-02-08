<?php

namespace App;

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
        $current = \Carbon\Carbon::now();
        return $current->addSeconds($value)->diffForHumans(null, true);
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
        if ($dt->diffInDays(\Carbon\Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }

    public function getAppliedToAttribute($value) {
        return $this->user_applied->username;
    }

    public function getCreatedByAttribute($value) {
        return $this->user_created->username;
    }

    protected $appends = [
        'applied_to', 'created_by',
    ];
}
