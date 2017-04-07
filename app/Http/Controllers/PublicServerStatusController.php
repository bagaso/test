<?php

namespace App\Http\Controllers;

use App\VpnServer;
use Illuminate\Http\Request;

class PublicServerStatusController extends Controller
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

        if (!$db_settings->settings['public_server_status']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error', 'public_credit_distributors' => $db_settings->settings['public_credit_distributors'], 'public_online_users' => $db_settings->settings['public_online_users']],
                'message' => 'Please Login to access this page.',
            ], 403);
        }

        $servers = VpnServer::select('server_name', 'access', 'limit_bandwidth', 'is_active')->withCount('online_users')->orderBy('server_name', 'asc')->get();

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Server Status';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $site_options['public_online_users'] = $db_settings->settings['public_online_users'];
        $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'];
        $site_options['public_server_status'] = $db_settings->settings['public_server_status'];

        return response()->json([
            'site_options' => $site_options,
            'model' => $servers,
        ], 200);
    }
}
