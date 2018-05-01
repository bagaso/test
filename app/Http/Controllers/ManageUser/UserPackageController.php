<?php

namespace App\Http\Controllers\ManageUser;

use App\SsPort;
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

        $user = User::find($id);
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
                'port_number' => auth()->user()->can('ss-port-pass-update') ? $user->user_port ? $user->user_port->id : '0' : '0',
                'ss_password' => auth()->user()->can('ss-port-pass-update') ? $user->value : '',
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
            'user_package_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('user_packages', 'id')->where(function ($query) {
                    $query->where([['is_active', 1]]);
                })
            ],
            'port_number' => [
                'bail',
                auth()->user()->can('ss-port-pass-update') ? 'required' : '',
                (auth()->user()->can('ss-port-pass-update') && $request->port_number <> 0) ?
                    ($request->port_number != ($user->user_port ? $user->user_port->id : 0)) ?
                        Rule::exists('ss_ports', 'id')->where(function ($query) {
                            $query->where([['is_reserved', 0],['user_id', 0]]);
                        })
                    : ''
                : '',
            ],
            'ss_password' => [
                'bail',
                auth()->user()->can('ss-port-pass-update') ? 'required' : '',
                auth()->user()->can('ss-port-pass-update') ? 'alpha_num' : '',
                auth()->user()->can('ss-port-pass-update') ? 'min:8' : '',
                auth()->user()->can('ss-port-pass-update') ? 'max:20' : '',
            ],
        ]);

        $current_copy = Carbon::now();
        $expired_at = Carbon::parse($user->getOriginal('expired_at'));
        
        $user_package = UserPackage::findorfail($request->user_package_id);

        if(auth()->user()->cannot('force-package-upgrade')) {
            $days_left = $current_copy->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost']);
            $dt = $current_copy->addSeconds($days_left);
            if($user_package->id > 1 && $dt->diffInDays() < intval($user_package->user_package['minimum_duration'])) {
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

        // unset previous port
        if($user->user_port) {
            SsPort::find($user->user_port->id)->update(['user_id' => 0]);
        }
        User::with('user_port')->where('id', $id)->update([
            'user_package_id' => $user_package->id,
            'expired_at' => $user->user_package_id <> $user_package->id ? $current->addSeconds($add) : $user->getOriginal('expired_at'),
            'value' => auth()->user()->can('ss-port-pass-update') ? $request->ss_password ? $request->ss_password : '' : $user->ss_password,
            'dl_speed' => auth()->user()->can('vpn-dl-up-update') ? $request->dl_speed ? $request->dl_speed : '0kbit' : $user->dl_speed,
            'up_speed' => auth()->user()->can('vpn-dl-up-update') ? $request->up_speed ? $request->up_speed : '0kbit' : $user->up_speed,
        ]);
        // set new port
        if($request->port_number != 0) {
            SsPort::find($request->port_number)->update(['user_id' => $user->id]);
        }

        $user = User::findorfail($user->id);

        return response()->json([
            'message' => 'User package updated.',
            'user_profile' => [
                'user_package_id' => $user->user_package_id,
                'user_package' => $user->user_package,
                'expired_at' => $user->expired_at,
                'port_number' => auth()->user()->can('ss-port-pass-update') ? $user->user_port ? $user->user_port->id : '0' : '0',
                'ss_password' => auth()->user()->can('ss-port-pass-update') ? $user->value : '',
                'ss_f_login' => $user->ss_f_login,
                'ss_login' => $user->user_package->ss_login,
                'dl_speed' => auth()->user()->can('vpn-dl-up-update') ? $user->dl_speed : '',
                'up_speed' => auth()->user()->can('vpn-dl-up-update') ? $user->up_speed : '',
            ]
        ], 200);
    }

    public function get_port($id)
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

        $newport = SsPort::where([['user_id', 0], ['is_reserved', 0]])->inRandomOrder()->first();

        return response()->json([
            'message' => 'New key generated.',
            'port_number' => $newport->id,
        ], 200);
    }
}
