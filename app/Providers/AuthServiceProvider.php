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

        Gate::define('update-user-profile', function ($user, $id) {
            $data = User::findorfail($id);
            if($data->user_group_id <= $user->user_group_id) {
                return false;
            }
            if($data->isDownline() && in_array('PCODE_002', json_decode($user->roles->pluck('code')))) {
                return true;
            }

            if(!$data->isDownline() && $user->user_group_id == 2 && in_array('PCODE_007', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('update-user-security', function ($user, $id) {
            if($user->id == $id) return false;
            $data = User::findorfail($id);
            //if user is a downline
            if($data->isDownline() && in_array('PCODE_003', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            //if user group is Ultimate (group_id = 2) and has a permission to manage other account code PCODE_007)
            if(!$data->isDownline() && $user->user_group_id == 2 && in_array('PCODE_008', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('create-user', function ($user) {
            if(in_array($user->user_group_id, [2,3,4]) && in_array('PCODE_001', json_decode($user->roles->pluck('code')))) return true;
            return false;
        });


        Gate::define('set-user-usergroup', function ($user) {
            if(in_array($user->user_group_id, [2,3])) return true;
            return false;
        });

        Gate::define('update-user-usergroup', function ($user, $id) {
            $data = User::findorfail($id);
            if(!in_array($user->user_group_id, [2,3])) return false;
            if($data->isDownline() && in_array('PCODE_011', json_decode($user->roles->pluck('code')))) {
                return true;
            }

            if(!$data->isDownline() && $user->user_group_id == 2 && in_array('PCODE_012', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

        Gate::define('set-user-status', function ($user) {
            if(in_array('PCODE_004', json_decode($user->roles->pluck('code')))) return true;
            return false;
        });

        Gate::define('update-user-status', function ($user, $id) {
            $data = User::findorfail($id);
            if(!in_array($user->user_group_id, [2,3])) return false;
            if($data->isDownline() && in_array('PCODE_005', json_decode($user->roles->pluck('code')))) {
                return true;
            }

            if(!$data->isDownline() && $user->user_group_id == 2 && in_array('PCODE_009', json_decode($user->roles->pluck('code')))) {
                return true;
            }
            return false;
        });

    }
}
