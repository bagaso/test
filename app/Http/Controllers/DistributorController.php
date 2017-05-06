<?php

namespace App\Http\Controllers;

use App\Lang;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DistributorController extends Controller
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

    public function index(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        
        $data = User::select('fullname', 'contacts', 'user_group_id', 'credits')->where([['distributor', 1], ['status_id', 2], ['credits', '>', 0]])->SearchDistPaginateAndOrder($request);

        $columns = [
            'fullname', 'contact', 'user_group_id', 'credits',
        ];

        $language = Lang::all()->pluck('name');

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = $language[21];
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['manage_vpn_server'] = auth()->user()->can('manage-vpn-server');
        $permission['manage_voucher'] = auth()->user()->can('manage-voucher');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'permission' => $permission,
            'model' => $data,
            'columns' => $columns
        ], 200);
    }
}
