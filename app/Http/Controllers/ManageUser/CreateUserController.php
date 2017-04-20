<?php

namespace App\Http\Controllers\ManageUser;

use App\Lang;
use App\SiteSettings;
use App\Status;
use App\User;
use App\UserGroup;
use App\UserPackage;
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
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (Gate::denies('manage-user')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('create-user')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to create user.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Create New User';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $usergroups = UserGroup::where('id', '>', auth()->user()->user_group->id)->get();
        $userpackage = UserPackage::all();
        $userstatus = Status::whereIn('id', [2,3])->get();

        $language = Lang::all();

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'language' => $language,
            'permission' => $permission,
            'user_group_list' => $usergroups,
            'user_package_list' => $userpackage,
            'user_status_list' => $userstatus,
        ]);
    }

    public function create(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (Gate::denies('is_subscribed')) {
            return response()->json([
                'message' => 'Your account is already expired please extend your duration.',
            ], 403);
        }

        if (Gate::denies('manage-user')) {
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
        
        $new_user = new User;
        $new_user->user_group_id = $request->user_group_id;
        $new_user->username = $request->username;
        $new_user->password = $request->password;
        $new_user->email = $request->email;
        $new_user->fullname = $request->fullname;
        $new_user->status_id = $request->status_id;
        $new_user->parent_id = auth()->user()->id;
        $new_user->consumable_data = $db_settings->settings['consumable_data'] * 1048576;
        $new_user->expired_at = $current->addSeconds($db_settings->settings['trial_period'] * 3600);
        $new_user->save();
        if(in_array($new_user->user_group_id, [2])) {
            $new_user->roles()->sync([1,2,3,4,5,6,11,13,15,16,18]);
        }
        return response()->json([
            'message' => 'New user created.'
        ], 200);
    }
}
