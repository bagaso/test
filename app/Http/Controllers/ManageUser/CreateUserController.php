<?php

namespace App\Http\Controllers\ManageUser;

use App\SiteSettings;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class CreateUserController extends Controller
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

    public function index()
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['create_user'] = auth()->user()->can('create-user');

        if (Gate::denies('manage-user')) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('create-user')) {
            return response()->json([
                'message' => 'No permission to create user.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $permission['set_user_group'] = auth()->user()->can('set-user-usergroup');
        $permission['set_user_status'] = auth()->user()->can('set-user-status');

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
        ]);
    }

    public function create(Request $request)
    {
        if (Gate::denies('manage-user') || Gate::denies('create-user')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        if (!auth()->user()->isAdmin() && auth()->user()->user_down_ctr->count() > 500) {
            return response()->json([
                'message' => 'You have reached user limit.',
            ], 403);
        }

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
            'username' => 'bail|required|alpha_num|between:6,20|unique:users,username',
            'password' => 'bail|required|between:6,15|confirmed',
            'password_confirmation' => 'bail|required|between:6,15',
            'email' => 'bail|required|email|max:50|unique:users,email',
            'fullname' => 'bail|required|max:50',
            'status_id' => 'bail|required|integer|in:0,1',
        ]);

        $current = Carbon::now();

        $site_settings = SiteSettings::find(1);

        //$new_user = new User::create($request->all());
        $new_user = new User;
        $new_user->user_group_id = $request->user_group_id;
        $new_user->username = $request->username;
        $new_user->password = $request->password;
        $new_user->email = $request->email;
        $new_user->fullname = $request->fullname;
        $new_user->status_id = $request->status_id;
        $new_user->consumable_data = $site_settings->settings['consumable_data'] * 1048576;
        $new_user->expired_at = $current->addSeconds($site_settings->settings['trial_period']);
        $new_user->save();
        if(in_array($new_user->user_group_id, [2,3,4])) {
            $new_user->roles()->sync([1,2,4,6,13,15,16,18]);
        }
        return response()->json([
            'message' => 'New user created.'
        ], 200);
    }
}
