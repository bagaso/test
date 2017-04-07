<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\User;
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

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'My Account';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $user = User::with('upline')->find(auth()->user()->id);

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => $user->username, 'email' => $user->email, 'fullname' => $user->fullname, 'contact' => $user->contact, 'distributor' => $user->distributor, 'created_at' => $user->created_at, 'updated_at' => $user->updated_at, 'user_group_id' => $user->user_group_id, 'credits' => $user->credits, 'expired_at' => $user->expired_at, 'consumable_data' => $user->consumable_data, 'status_id' => $user->status_id, 'vpn_session' => $user->vpn_session],
            'upline' => $user->upline->username,
            'permission' => $permission,
            'vpn_session' => \App\OnlineUser::where('user_id', auth()->user()->id)->count(),
        ], 200);
    }

    public function update(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $account = $request->user();

        $this->validate($request, [
            'username' => 'sometimes|bail|required|alpha_num|between:6,20|unique:users,username,' . $account->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $account->id,
            'fullname' => 'bail|required|max:50',
            'contact' => 'required_if:distributor,true',
            'distributor' => 'bail|required|boolean',
        ]);

        if (auth()->user()->isAdmin()) {
            $account->username = $request->username;
        }
        $account->email = $request->email;
        $account->fullname = $request->fullname;
        $account->contact = $request->contact;
        if(in_array(auth()->user()->user_group_id, [2,3,4])) {
            $account->distributor = $request->distributor;
        }
        $account->save();

        $user = auth()->user();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => ['username' => $user->username, 'email' => $user->email, 'fullname' => $user->fullname, 'contact' => $user->contact, 'distributor' => $user->distributor, 'created_at' => $user->created_at, 'updated_at' => $user->updated_at, 'user_group_id' => $user->user_group_id, 'credits' => $user->credits, 'expired_at' => $user->expired_at, 'consumable_data' => $user->consumable_data, 'status_id' => $user->status_id, 'vpn_session' => $user->vpn_session],
        ], 200);

    }

}