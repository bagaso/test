<?php

namespace App\Http\Controllers\ManageUser;

use App\SsPorts;
use App\User;
use App\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserPackageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function index($id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user')) {
            return response()->json([
                'message' => 'No permission to access this page.',
            ], 403);
        }

        if (Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'No permission update this user.'
            ], 403);
        }

        $user = User::with('user_package')->find($id);
        if(auth()->user()->isAdmin() || auth()->user()->isSubAdmin()) {
            $userpackage = UserPackage::where([['is_active', 1]])->get();
        } else {
            $userpackage = UserPackage::where([['is_active', 1], ['is_public', 1]])->get();
        }

        $permission['vpn-dl-up-update'] = auth()->user()->can('vpn-dl-up-update');
        $permission['ss-port-pass-update'] = auth()->user()->can('ss-port-pass-update');

        return response()->json([
            'permission_userpackage_page' => $permission,
            'user_profile' => [
                'username' => $user->username,
                'user_package_id' => $user->user_package_id,
                'port_number' => auth()->user()->can('ss-port-pass-update') ? $user->port_number : '',
                'ss_password' => auth()->user()->can('ss-port-pass-update') ? $user->ss_password : '',
                'ss_f_login' => $user->ss_f_login,
                'ss_login' => $user->user_package->ss_login,
                'dl_speed' => auth()->user()->can('vpn-dl-up-update') ? $user->dl_speed : '',
                'up_speed' => auth()->user()->can('vpn-dl-up-update') ? $user->up_speed : '',
            ],
            'user_package_list' => $userpackage,
        ], 200);
    }

    public function user_package(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::with('user_package')->findorfail($id);

        $this->validate($request, [
            'user_package_id' => 'bail|required|integer|in:1,2,3,4,5',
            'port_number' => [
                'bail',
                auth()->user()->can('ss-port-pass-update') ? 'required' : '',
                (auth()->user()->can('ss-port-pass-update') && $request->port_number <> 0 && ($user->ss_f_login || $user->user_package->ss_login)) ?
                    Rule::unique('users')->ignore($id) : '',
                (auth()->user()->can('ss-port-pass-update') && $request->port_number <> 0 && ($user->ss_f_login || $user->user_package->ss_login)) ?
                    Rule::exists('ss_ports')->where(function ($query) use ($user, $request) {
                        $query->where('is_reserved', 0);
                }) : '',
            ],
            'ss_password' => [
                'bail',
                auth()->user()->can('ss-port-pass-update') ? 'alpha_num' : '',
                auth()->user()->can('ss-port-pass-update') ? 'min:8' : '',
                auth()->user()->can('ss-port-pass-update') ? 'max:20' : '',
            ],
        ]);

        $current_copy = Carbon::now();
        $expired_at = Carbon::parse($user->getOriginal('expired_at'));
        
        $user_package = UserPackage::findorfail($request->user_package_id);

        if(auth()->user()->cannot('force-package-upgrade')) {
            // $days_left = $current_copy->diffInSeconds($expired_at);
            $days_left = $current_copy->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost']);
            $dt = $current_copy->addSeconds($days_left);
            if($user_package->id > 1 && $dt->diffInDays() <= intval($user_package->user_package['minimum_duration'])) {
                return response()->json([
                    'message' => 'User must have ' . $user_package->user_package['minimum_duration'] . ' day(s) of remaining duration.',
                ], 403);
            }
        }

        #update user Down and Up set 0kbit for no speed limit only for openvpn
        if(auth()->user()->can('vpn-dl-up-update')) {
            //$user->dl_speed = $request->dl_speed ? $request->dl_speed : '0kbit';
            //$user->up_speed = $request->up_speed ? $request->up_speed : '0kbit';
        }

        $current = Carbon::now();

        $add = 0;
        if($user->user_package_id <> $user_package->id && $current->lt($expired_at)) {
            $add = ($current->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost'])) / intval($user_package->user_package['cost']);
        }
        User::where('id', $id)->update([
            'user_package_id' => $user_package->id,
            'expired_at' => $user->user_package_id <> $user_package->id ? $current->addSeconds($add) : $user->getOriginal('expired_at'),
            'dl_speed' => auth()->user()->can('vpn-dl-up-update') ? $request->dl_speed ? $request->dl_speed : '0kbit' : $user->dl_speed,
            'up_speed' => auth()->user()->can('vpn-dl-up-update') ? $request->up_speed ? $request->up_speed : '0kbit' : $user->up_speed,
            'port_number' => auth()->user()->can('ss-port-pass-update') ? $request->port_number : $user->port_number,
            'ss_password' => auth()->user()->can('ss-port-pass-update') ? $request->ss_password ? $request->ss_password : '' : $user->ss_password,
        ]);

        $user = User::with('user_package')->findorfail($user->id);

        return response()->json([
            'message' => 'User package updated.',
            'user_profile' => [
                'user_package_id' => $user->user_package_id,
                'user_package' => $user->user_package,
                'expired_at' => $user->expired_at,
                'port_number' => auth()->user()->can('ss-port-pass-update') ? $user->port_number : '',
                'ss_password' => auth()->user()->can('ss-port-pass-update') ? $user->ss_password : '',
                'ss_f_login' => $user->ss_f_login,
                'ss_login' => $user->user_package->ss_login,
                'dl_speed' => auth()->user()->can('vpn-dl-up-update') ? $user->dl_speed : '',
                'up_speed' => auth()->user()->can('vpn-dl-up-update') ? $user->up_speed : '',
            ]
        ], 200);
    }
}
