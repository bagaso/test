<?php

namespace App\Http\Controllers\ManageUser;

use App\Role;
use App\User;
use App\UserGroup;
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

        if(auth()->user()->id == $id || !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'No permission to access this page.',
            ], 403);
        }

        $user = User::findOrFail($id);
        if($user->user_group->id > 2) {
            return response()->json([
                'message' => 'Permission is Only available only to ' . UserGroup::findorfail(2)->name . '.',
            ], 403);
        }

        $permission_list = Role::all();
        
        return response()->json([
            'user_profile' => ['username' => $user->username],
            'user_permission' => $user->roles()->pluck('id'),
            'permission_list' => $permission_list,
        ], 200);
    }

    public function updatePermission(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $user = User::findorfail($id);

        if (!auth()->user()->isAdmin() || auth()->user()->id == $user->id || !$user->isSubAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'permission' => 'array',
        ]);

        $user->roles()->sync($request->permission);
        return response()->json([
            'message' => 'User permission updated.',
        ], 200);
    }
}
