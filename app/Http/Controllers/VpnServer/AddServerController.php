<?php

namespace App\Http\Controllers\VpnServer;

use App\Lang;
use App\ServerAccess;
use App\SiteSettings;
use App\UserPackage;
use App\VpnServer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

class AddServerController extends Controller
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

        $language = Lang::all()->pluck('name');

        $userpackage = UserPackage::all();

        if (auth()->user()->cannot('manage-vpn-server')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'user_package_list' => $userpackage->pluck('name'),
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'VPN Server : Create';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $serveraccess = ServerAccess::all();
        $userpackage = UserPackage::all();

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'server_access_list' => $serveraccess,
            'user_package_list' => $userpackage,
            'permission' => $permission,
        ], 200);
    }

    public function addServer(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (auth()->user()->cannot('manage-vpn-server')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'server_name' => 'bail|required|unique:vpn_servers,server_name',
            'server_ip' => 'bail|required|ip|unique:vpn_servers,server_ip',
            'server_domain' => 'bail|required|unique:vpn_servers,server_domain',
            'web_port' => 'bail|required|integer',
            'server_key' => 'bail|required|unique:vpn_servers,server_key',
            'vpn_secret' => 'required',
            'server_port' => 'bail|required|integer',
            'server_access' => 'bail|required|in:1,2,3',
            'server_status' => 'bail|required|boolean',
            'user_package' => 'bail|required|array',
            'limit_bandwidth' => 'bail|required|boolean',
        ]);

        $site_settings = SiteSettings::find(1);

        $client = new Client(['base_uri' => 'https://api.cloudflare.com']);
        $response = $client->request('POST', "/client/v4/zones/{$site_settings->settings['cf_zone']}/dns_records",
            ['http_errors' => false, 'headers' => ['X-Auth-Email' => 'mp3sniff@gmail.com', 'X-Auth-Key' => 'ff245b46bd71002891e2890059b122e80b834', 'Content-Type' => 'application/json'], 'json' => ['type' => 'A', 'name' => $request->server_domain, 'content' => $request->server_ip]]);

        $cloudflare = json_decode($response->getBody());

        if(!$cloudflare->success) {
            return response()->json([
                'message' => 'Cloudflare: ' . $cloudflare->errors[0]->message,
            ], 403);
        }

        $server = new VpnServer;
        $server->cf_id = '11';//$cloudflare->result->id;
        $server->server_name = $request->server_name;
        $server->server_ip = $request->server_ip;
        $server->server_domain = $request->server_domain;
        $server->web_port = $request->web_port;
        $server->server_key = $request->server_key;
        $server->server_port = $request->server_port;
        $server->vpn_secret = $request->vpn_secret;
        $server->server_access_id = $request->server_access;
        $server->is_active = $request->server_status;
        $server->limit_bandwidth = (int)$request->limit_bandwidth;
        $server->save();
        $server->user_packages()->sync($request->user_package);

        return response()->json([
            'message' => 'New server added.',
        ], 200);
    }
}
