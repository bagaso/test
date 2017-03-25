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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (auth()->user()->id == $id || Gate::denies('manage-user')) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('update-user-profile', $id)) {
            return response()->json([
                'message' => 'No permission to update user profile.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }
        if(Gate::denies('update-user-security', $id)) {
            return response()->json([
                'message' => 'No permission to update user security.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);

        $permission['create_user'] = auth()->user()->can('create-user');

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'user_profile' => $user
        ], 200);
    }

    public function updateSecurity(Request $request, $id)
    {
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
