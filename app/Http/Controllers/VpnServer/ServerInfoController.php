<?php

namespace App\Http\Controllers\VpnServer;

use App\Lang;
use App\ServerAccess;
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
        $permission['manage_vpn_server'] = auth()->user()->can('manage-vpn-server');
        $permission['manage_voucher'] = auth()->user()->can('manage-voucher');
        $permission['manage_update_json'] = auth()->user()->can('manage-update-json');

        $language = Lang::all()->pluck('name');

        if (auth()->user()->cannot('manage-vpn-server')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $server_info = VpnServer::with(['user_packages' => function($query) {
            $query->pluck('id');
        }])->findOrFail($id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'VPN Server : Info';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $serveraccess = ServerAccess::where('is_active', 1)->get();
        $userpackage = UserPackage::where('is_active', 1)->get();
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'server_access_list' => $serveraccess,
            'user_package_list' => $userpackage,
            'permission' => $permission,
            'server_info' => $server_info,
            'user_packages' => $server_info->user_packages->pluck('id'),
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
        

        if (auth()->user()->cannot('manage-vpn-server')) {
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
            'server_access' => 'bail|required|in:1,2,3,4,5,6,7',
            'server_status' => 'bail|required|boolean',
            'user_package' => 'bail|required|array|in:1,2,3,4,5',
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
        
        if (auth()->user()->cannot('manage-vpn-server')) {
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
