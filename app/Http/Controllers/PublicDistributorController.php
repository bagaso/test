<?php

namespace App\Http\Controllers;

use App\Lang;
use App\User;
use Illuminate\Http\Request;

class PublicDistributorController extends Controller
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
    
    public function index(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        $language = Lang::all();

        if (!$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'Maintenance Mode.',
                'language' => $language,
            ], 401);
        }

        if (!$db_settings->settings['public_credit_distributors']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error', 'public_online_users' => $db_settings->settings['public_online_users'], 'public_server_status' => $db_settings->settings['public_server_status']],
                'message' => 'Please Login to access this page.',
                'language' => $language,
            ], 403);
        }

        $data = User::where([['distributor', 1], ['status_id', 1], ['credits', '>', 0]])->SearchDistPaginateAndOrder($request);

        $columns = [
            'fullname', 'contact', 'user_group_id', 'credits',
        ];

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Credit Distributors';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $site_options['public_online_users'] = $db_settings->settings['public_online_users'];
        $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'];
        $site_options['public_server_status'] = $db_settings->settings['public_server_status'];

        return response()->json([
            'site_options' => $site_options,
            'language' => $language,
            'model' => $data,
            'columns' => $columns
        ], 200);
    }
}
