<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth:api']);

    } // function __construct

    public function index()
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'vpn_session' => \App\OnlineUser::where('user_id', auth()->user()->id)->count(),
        ], 200);
    }

    public function update(Request $request)
    {
        if (Gate::denies('update-account')) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }

        $account = $request->user();

        $this->validate($request, [
            'username' => 'sometimes|bail|required|alpha_num|between:6,20|unique:users,username,' . $account->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $account->id,
            'fullname' => 'bail|required|max:50',
        ]);

        if (auth()->user()->isAdmin()) {
            $account->username = $request->username;
        }
        $account->email = $request->email;
        $account->fullname = $request->fullname;
        $account->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => $account
        ], 200);

    } // function update

} // end class