<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserSecurityController extends Controller
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

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (auth()->user()->id == $id || Gate::denies('manage-user')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('update-user-profile', $id)) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to update user profile.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }
        if(Gate::denies('update-user-security', $id)) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to update user security.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User Security';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['create_user'] = auth()->user()->can('create-user');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'permission' => $permission,
            'user_profile' => $user
        ], 200);
    }

    public function updateSecurity(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if(auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user-profile', $id) || Gate::denies('update-user-security', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findorfail($id);

        $this->validate($request, [
            'new_password' => 'bail|required|between:6,15|confirmed',
            'new_password_confirmation' => 'bail|required|between:6,15',
        ]);

        $user->password = $request->new_password;
        $user->save();

        return response()->json([
            'message' => 'User security updated.',
        ], 200);

    }

}
