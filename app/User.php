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
        'username', 'user_group_id', 'user_package_id', 'status_id', 'credits', 'expired_at', 'created_at'
    ];

    protected $casts = [
        'distributor' => 'boolean',
        'vpn_f_login' => 'boolean',
        'ss_f_login' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'upline', 'parent_id', 'user_group_id', 'status_id', 'user_package_id', 'user_down_ctr', 'ss_port', 'ss_password',
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
        return $query->whereIn('user_group_id', [3,4])
            ->orderBy($request->column, $request->direction)
            ->where(function($query) use ($request) {
                if($request->has('search_input')) {
                    $query->where($request->search_column, 'LIKE', '%'.trim($request->search_input).'%');
                }
            })->paginate($request->per_page);
    }

    public function status()
    {
        return $this->belongsTo('App\Status');
    }

    public function user_group()
    {
        return $this->belongsTo('App\UserGroup');
    }

    public function user_package()
    {
        return $this->belongsTo('App\UserPackage');
    }
    
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
    
    public function vpn() {
        return $this->hasMany('App\OnlineUser');
    }

    public function credit_logs() {
        return $this->hasMany('App\UserCreditLog');
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
        return $this->user_group->id == 1;
    }

    public function isSubAdmin()
    {
        return $this->user_group->id == 2;
    }

    public function isDownline()
    {
        if($this->isAdmin()) return false;
        if(auth()->user()->isAdmin() || auth()->user()->id == $this->parent_id) return true;
        return false;
    }

    public function upline()
    {
        return $this->belongsTo('App\User', 'parent_id')->select('id', 'username');
    }

    public function user_down_ctr()
    {
        return $this->hasMany('App\User', 'parent_id');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }

    public function getTotalUsersAttribute()
    {
        return  $this->user_down_ctr->count();
    }

    public function getTotalUserResellersAttribute()
    {
        return  $this->user_down_ctr()->where('user_group_id', 3)->count();
    }

    public function getTotalUserSubresellersAttribute()
    {
        return  $this->user_down_ctr()->where('user_group_id', 4)->count();
    }

    public function getTotalUserClientsAttribute()
    {
        return  $this->user_down_ctr()->where('user_group_id', 5)->count();
    }
    
    public function getStatusIdAttribute($value) {
        return $this->isAdmin() ? 2 : $value;
    }

    public function getCreditsAttribute($value)
    {
        return $this->isAdmin() || $this->can('unlimited-credits') ? 'No Limit' : $value;
    }

    public function isActive() {
        return ($this->isAdmin() || $this->status->id == 2);
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

    public function getUplineUsernameAttribute()
    {
        return $this->upline;
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

    protected $appends = ['upline_username', 'total_users', 'total_user_resellers', 'total_user_subresellers', 'total_user_clients'];
}
