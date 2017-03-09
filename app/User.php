<?php

namespace App;

use Illuminate\Http\Request;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use SoftDeletes, HasAPiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password', 'email', 'fullname', 'user_group_id', 'status_id', 'parent_id', 'expired_at',
    ];

    public static $columns = [
        'username', 'email', 'user_group_id', 'vpn_session', 'status_id', 'expired_at', 'created_at'
    ];

    protected $casts = [
        'distributor' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = [
        'expired_at', 'deleted_at',
    ];

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

    public function findForPassport($username) {
        return $this->where('username', $username)->first();
    }

    public function scopeSearchPaginateAndOrder($query, $request)
    {

        return $query->where([['id', '<>', auth()->user()->id],['user_group_id', '<>', 1],['user_group_id', '>', auth()->user()->user_group_id]])
        ->orderBy($request->column, $request->direction)
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

    public function scopeSearchDistPaginateAndOrder($query, $request)
    {

        return $query->where([['user_group_id', '<>', 1]])
            ->orderBy($request->column, $request->direction)
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

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function setParentIdAttribute($value)
    {
        $this->attributes['parent_id'] = auth()->user()->id;
    }
    
    public function vpn() {
        return $this->hasMany('App\OnlineUser');
    }

    public function vouchers_applied() {
        return $this->hasMany('App\VoucherCode');
    }

    public function vouchers_generated() {
        return $this->hasMany('App\VoucherCode', 'created_user_id');
    }

    public function getCreatedAtAttribute($value) {
        $dt = Carbon::parse($value);
        if ($dt->diffInDays(Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }

    public function getUpdatedAtAttribute($value) {
        $dt = Carbon::parse($value);
        if ($dt->diffInDays(Carbon::now()) > 1)
            return $dt->format('Y-M-d');
        else
            return $dt->diffForHumans();
    }

    public function getExpiredAtAttribute($value) {
        $current = Carbon::now();
        $dt = Carbon::parse($value);
        if($this->isAdmin()) {
            return 'No Limit';
        }
        if($current->gte($dt)) {
            return 'Expired';
        }
        if ($dt->diffInDays(Carbon::now()) > 1)
            return $dt->diffInDays(Carbon::now()) . ' Days';
        else
            return $dt->diffForHumans(null, true);
    }

    public function isAdmin()
    {
        return $this->user_group_id == 1;
    }

    public function isDownline()
    {
        if($this->isAdmin()) return false;
        if(auth()->user()->isAdmin() || auth()->user()->id == $this->parent_id) return true;
        return false;
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }
    
    public function getStatusIdAttribute($value) {
        return $this->isAdmin() ? 1 : $value;
    }

    public function getCreditsAttribute($value)
    {
        return $this->isAdmin() ? 'No Limit' : $value;
    }

    public function isActive() {
        return ($this->isAdmin() || $this->status_id == 1);
    }

    public function getPermissionAttribute()
    {
        return array(
                'is_admin' => $this->isAdmin(),
                'update_account' => $this->can('update-account'),
                'manage_user' => $this->can('manage-user'),
                'create_user' => $this->can('create-user')
        );
    }

    public function getConsumableDataAttribute($value)
    {
        return $this->sizeformat($value);
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
}
