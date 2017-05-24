<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
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
        $permission['manage_vpn_server'] = auth()->user()->can('manage-vpn-server');
        $permission['manage_voucher'] = auth()->user()->can('manage-voucher');
        $permission['manage_update_json'] = auth()->user()->can('manage-update-json');

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Security';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $language = Lang::all()->pluck('name');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'distributor' => auth()->user()->distributor],
            'language' => $language,
            'permission' => $permission
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

        if($request->password != '' && !Hash::check($request->password, $account->getAuthPassword())) {
            return response()->json([
                'password' => ['Your old password is incorrect.'],
            ], 422);
        }

        $this->validate($request, [
            'password' => 'required',
            'new_password' => 'bail|required|between:6,15|confirmed',
            'new_password_confirmation' => 'bail|required|between:6,15',
        ]);

        $account->password = $request->new_password;
        $account->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ], 200);

    }

}