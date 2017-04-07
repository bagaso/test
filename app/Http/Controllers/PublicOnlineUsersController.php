<?php

namespace App\Http\Controllers;

use App\OnlineUser;
use Illuminate\Http\Request;

class PublicOnlineUsersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index()
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (!$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'Maintenance Mode.',
            ], 401);
        }

        if (!$db_settings->settings['public_online_users']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error', 'public_credit_distributors' => $db_settings->settings['public_credit_distributors'], 'public_server_status' => $db_settings->settings['public_server_status']],
                'message' => 'Please Login to access this page.',
            ], 403);
        }

        $data = OnlineUser::with('user', 'vpnserver')->orderBy('vpn_server_id', 'asc')->paginate(50);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Online Users';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $site_options['public_online_users'] = $db_settings->settings['public_online_users'];
        $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'];
        $site_options['public_server_status'] = $db_settings->settings['public_server_status'];

        return response()->json([
            'site_options' => $site_options,
            'model' => $data,
        ], 200);
    }
}
