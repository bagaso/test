<?php

namespace App\Http\Controllers;

use App\Lang;
use App\SiteSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
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
        $db_settings = SiteSettings::findorfail(1);
        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Login';

        $client_id = DB::table('oauth_clients')->where('id', 2)->first();

        $language = Lang::all();

        if(parse_url(request()->headers->get('referer'), PHP_URL_HOST) == parse_url($db_settings->settings['domain'], PHP_URL_HOST)) {
            $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
            $site_options['public_online_users'] = $db_settings->settings['public_online_users'] && $site_options['enable_panel_login'];
            $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'] && $site_options['enable_panel_login'];
            $site_options['public_server_status'] = $db_settings->settings['public_server_status'] && $site_options['enable_panel_login'];
            return response()->json([
                'site_options' => $site_options,
                'language' => $language,
                'client_secret' => $client_id->secret,
            ], 200);
        } else {
            return response()->json([
                'site_options' => $site_options,
                'language' => $language,
                'message' => 'Error.',
            ], 403);
        }

    }
}
