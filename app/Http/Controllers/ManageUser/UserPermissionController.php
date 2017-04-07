<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserPermissionController extends Controller
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

        if(auth()->user()->id == $id || !auth()->user()->isAdmin()) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);
        if($user->user_group_id > 4) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'This user permission is not available upgrade user to reseller or higher.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User Permission';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['create_user'] = auth()->user()->can('create-user');
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'permission' => $permission,
            'user_profile' => $user,
            'user_permission' => $user->roles
        ], 200);
    }

    public function updatePermission($id, $p_code)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $user = User::findorfail($id);
        if(auth()->user()->id == $id || !auth()->user()->isAdmin() || (in_array($p_code, [7,8,9,10,12,14,17,19]) && $user->user_group_id != 2)) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }
        $user->roles()->toggle($p_code);
        return response()->json('', 200);
    }
}
