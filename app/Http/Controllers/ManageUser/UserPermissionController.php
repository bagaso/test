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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if(auth()->user()->id == $id || !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);
        if($user->user_group_id > 4) {
            return response()->json([
                'message' => 'This user permission is not available upgrade user to reseller or higher.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $permission['create_user'] = auth()->user()->can('create-user');
        
        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'user_profile' => $user,
            'user_permission' => $user->roles
        ], 200);
    }

    public function updatePermission($id, $p_code)
    {
        $user = User::findorfail($id);
        if(auth()->user()->id == $id || !auth()->user()->isAdmin() || (in_array($p_code, [7,8,9,10,12,14,17,19]) && $user->user_group_id != 2)) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }
        $user->roles()->toggle($p_code);
        return response()->json('', 200);
    }
}
