<?php

namespace App\Http\Controllers\ManageUser;

use App\Status;
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
                'message' => 'No permission to update this user',
            ], 403);
        }

        $user = User::findOrFail($id);
        $user_upline = User::with('upline')->find($id);

        $userstatus = Status::all();

        return response()->json([
            'update_username' => auth()->user()->can('update-username'), 
            'user_profile' => $user,
            'user_status_list' => $userstatus,
            'user_vpn_session' => \App\OnlineUser::with('vpnserver')->where('user_id', $user->id)->get(),
        ], 200);
    }

    public function updateProfile(Request $request, $id)
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

        $user = User::findOrFail($id);

        $this->validate($request, [
            'username' => 'bail|required|alpha_num|between:6,20|unique:users,username,' . $user->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $user->id,
            'fullname' => 'bail|required|max:50',
            'max_users' => 'bail|required|integer|min:' . $user->user_down_ctr->count(),
            'status.id' => 'bail|required|in:1,2,3',
        ]);

        $upline_user_id = 1;

        if(auth()->user()->isAdmin() && strlen(trim($request->upline_username['username'])) > 0) {
            try {
                $upline = User::where('username', $request->upline_username['username'])->firstorfail();
                if($upline->id == $user->id) {
                    return response()->json([
                        'message' => 'Action not allowed.',
                    ], 403);
                }
                $upline_user_id = $upline->id;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
                return response()->json([
                    'upline' => ['User not found.'],
                ], 422);
            }
        }


        if (auth()->user()->can('update-username')) {
            if($user->username <> $request->username) {
                if($user->vpn->count() > 0) {
                    return response()->json([
                        'message' => 'Username cannot be change at this moment.',
                    ], 403);
                }
                $user->username = $request->username;
            }
        }

        if(auth()->user()->isAdmin() || auth()->user()->isSubAdmin()) {
            $user->max_users = $request->max_users;
        }

        $user->email = $request->email;
        $user->fullname = $request->fullname;
        $user->status_id = $request->status['id'];
        if(auth()->user()->isAdmin()) {
            $user->parent_id = $upline_user_id;
        }
        $user->save();

        $user = User::with('status')->findOrFail($id);

        return response()->json([
            'message' => 'User profile updated.',
            'user_profile' => $user,
        ], 200);
    }

}
