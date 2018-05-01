<?php

namespace App\Providers;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
        
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
            if(!$user->isActive()) {
                return false;
            }
        });
        
        Gate::define('update-account', function ($user) {
            return true;
        });

        Gate::define('manage-user', function ($user) {
            if(in_array($user->user_group_id, [2,3,4])) return true;
            return false;
        });

        Gate::define('manage-all-users', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_001', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('update-user', function ($user, $id) {
            $data = User::findorfail($id);
            if($data->user_group->id <= $user->user_group->id) {
                return false;
            }
            if($data->isDownline() || (in_array($user->user_group->id, [2]) && in_array('PCODE_001', json_decode($user->roles->pluck('code'))))) {
                return true;
            }
            return false;
        });

        Gate::define('update-username', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_002', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('delete-user', function ($user, $id) {
            $data = User::findorfail($id);
            if($data->user_group->id <= $user->user_group->id) {
                return false;
            }
            if($data->isDownline() || (in_array($user->user_group->id, [2]) && in_array('PCODE_003', json_decode($user->roles->pluck('code'))))) {
                return true;
            }
            return false;
        });

        Gate::define('unlimited-credits', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_004', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('minus-credits', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_005', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('force-package-upgrade', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_006', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('update-user-duration', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_007', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('manage-vpn-server', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_008', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('manage-voucher', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_009', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('manage-update-json', function ($user) {
            if(in_array($user->user_group->id, [2]) && in_array('PCODE_010', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        #
        Gate::define('vpn-dl-up-update', function ($user) {
            return false;
        });

        #
        Gate::define('ss-port-pass-update', function ($user) {
            if(in_array($user->user_group->id, [2,3,4])) {
                return true;
            }
            return false;
        });

        Gate::define('account-ss-port-pass-update', function ($user) {
            return true;
        });
        
    }
}
