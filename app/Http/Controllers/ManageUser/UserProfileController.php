<?php

namespace App\Http\Controllers\ManageUser;

use App\Jobs\JobVpnDisconnectUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserProfileController extends Controller
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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (Gate::denies('manage-user') || auth()->user()->id == $id) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('update-user-profile', $id)) {
            return response()->json([
                'message' => 'No permission to View / Update user profile.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);

        $permission['create_user'] = auth()->user()->can('create-user');
        $permission['update_user_security'] = auth()->user()->can('update-user-security', $user->id);
        $permission['update_user_usergroup'] = auth()->user()->can('update-user-usergroup', $user->id);
        $permission['update_user_status'] =  auth()->user()->can('update-user-status', $user->id);
        $permission['update_user_credits'] = auth()->user()->can('transfer-credits', $user->id);
        $permission['apply_user_voucher'] = auth()->user()->can('apply-voucher', $user->id);

        $user_upline = User::with('upline')->find($id);

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'user_profile' => $user,
            'user_upline' => $user_upline->upline->username,
            'no_of_users' => $user->user_down_ctr->count(),
            'user_vpn_session' => \App\OnlineUser::with('vpnserver')->where('user_id', $user->id)->get(),
        ], 200);
    }

    public function updateProfile(Request $request, $id)
    {
        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user-profile', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findOrFail($id);

        if(auth()->user()->user_group_id == 1) {
            $usergroups = '2,3,4,5';
        }
        if(auth()->user()->user_group_id == 2) {
            $usergroups = '3,4,5';
        }
        if(auth()->user()->user_group_id == 3) {
            $usergroups = '4,5';
        }
        if(auth()->user()->user_group_id == 4) {
            $usergroups = '5';
        }

        $this->validate($request, [
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
            'username' => 'bail|required|alpha_num|between:6,20|unique:users,username,' . $user->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $user->id,
            'fullname' => 'bail|required|max:50',
            'status_id' => 'bail|required|integer|in:0,1,2',
        ]);

        if (Gate::allows('update-user-usergroup', $user->id)) {
            if ($user->user_group_id <> $request->user_group_id) {
                $user->roles()->sync([1,2,4,6]);
            }
            if ($request->user_group_id == 5) {
                $user->roles()->detach();
            }
            $user->user_group_id = $request->user_group_id;
        }

        if (auth()->user()->isAdmin()) {
            if($user->username <> $request->username) {
                if($user->vpn->count() > 0) {
                    return response()->json([
                        'message' => 'Username cannot be change at this moment.',
                    ], 403);
                }
                $user->username = $request->username;
            }
        }

        if (Gate::allows('update-user-status', $user->id)) {
            $user->status_id = $request->status_id;
            if(in_array($request->status_id, [0,2])) {
                foreach ($user->vpn as $vpn) {
                    $job = (new JobVpnDisconnectUser($user->username, $vpn->vpnserver->server_ip, $vpn->vpnserver->server_port))->delay(Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                    dispatch($job);
                }
            }
        }

        $user->email = $request->email;
        $user->fullname = $request->fullname;
        $user->save();

        return response()->json([
            'message' => 'User profile updated.',
            'user_profile' => $user,
        ], 200);
    }

}