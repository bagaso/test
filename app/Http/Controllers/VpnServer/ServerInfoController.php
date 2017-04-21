<?php

namespace App\Http\Controllers\VpnServer;

use App\Lang;
use App\SiteSettings;
use App\User;
use App\UserPackage;
use App\VpnServer;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ServerInfoController extends Controller
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
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');

        $language = Lang::all();

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $server_info = VpnServer::findOrFail($id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'VPN Server : Info';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $userpackage = UserPackage::all();
        

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'user_package_list' => $userpackage->pluck('name'),
            'permission' => $permission,
            'server_info' => $server_info,
        ], 200);
    }

    public function updateServer(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $server = VpnServer::findorfail($id);

        $this->validate($request, [
            'server_name' => 'bail|required|unique:vpn_servers,server_name,' . $server->id,
            'server_ip' => 'bail|required|ip|unique:vpn_servers,server_ip,' . $server->id,
            'server_domain' => 'bail|required|unique:vpn_servers,server_domain,' . $server->id,
            'web_port' => 'bail|required|integer',
            'server_key' => 'bail|required|unique:vpn_servers,server_key,' . $server->id,
            'server_access' => 'bail|required|in:0,1,2',
            'server_status' => 'bail|required|boolean',
            'package_1' => 'bail|required|boolean',
            'package_2' => 'bail|required|boolean',
            'package_3' => 'bail|required|boolean',
            'limit_bandwidth' => 'bail|required|boolean',
        ]);

        if($server->online_users->count() > 0 && $server->server_ip <> $request->server_ip) {
            return response()->json([
                'message' => 'Server IP cannot be change at this moment.',
            ], 403);
        }

        $site_settings = SiteSettings::find(1);

        $client = new Client(['base_uri' => 'https://api.cloudflare.com']);
        $response = $client->request('PUT', "/client/v4/zones/{$site_settings->settings['cf_zone']}/dns_records/{$server->cf_id}",
            ['http_errors' => false, 'headers' => ['X-Auth-Email' => 'mp3sniff@gmail.com', 'X-Auth-Key' => 'ff245b46bd71002891e2890059b122e80b834', 'Content-Type' => 'application/json'], 'json' => ['type' => 'A', 'name' => $request->server_domain, 'content' => $request->server_ip]]);

        $cloudflare = json_decode($response->getBody());

        if(!$cloudflare->success) {
            return response()->json([
                'message' => 'Cloudflare: ' . $cloudflare->errors[0]->message,
            ], 403);
        }

        $allowed_userpackage['package_1'] = (int)$request->package_1;
        $allowed_userpackage['package_2'] = (int)$request->package_2;
        $allowed_userpackage['package_3'] = (int)$request->package_3;

        $server->server_name = $request->server_name;
        $server->server_ip = $request->server_ip;
        $server->server_domain = $request->server_domain;
        $server->web_port = $request->web_port;
        $server->server_key = $request->server_key;
        $server->server_port = $request->server_port;
        $server->vpn_secret = $request->vpn_secret;
        $server->access = $request->server_access;
        $server->is_active = $request->server_status;
        $server->allowed_userpackage = $allowed_userpackage;
        $server->limit_bandwidth = (int)$request->limit_bandwidth;
        $server->save();

        return response()->json([
            'message' => 'Server updated.',
        ], 200);
    }
    
    public function generatekey()
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }
        
        $newkey = str_random(32);

        return response()->json([
            'message' => 'New key generated.',
            'newkey' => $newkey,
        ], 200);
    }
}
