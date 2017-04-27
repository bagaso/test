<?php

namespace App\Http\Controllers\VpnServer;

use App\Lang;
use App\SiteSettings;
use App\VpnServer;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ListServerController extends Controller
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
        
        if(auth()->user()->cannot('manage-vpn-server')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }
        
        $servers = VpnServer::select('id', 'server_name', 'server_ip', 'server_domain', 'server_access_id', 'limit_bandwidth', 'is_active')->with('server_access')->withCount('online_users')->orderBy('server_name', 'asc')->get();

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'VPN Server : List';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'permission' => $permission,
            'model' => $servers,
        ], 200);
    }

    public function serverstatus()
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

        $servers = VpnServer::with(['server_access', 'user_packages'])->select('id', 'server_name', 'server_access_id', 'limit_bandwidth', 'is_active')->withCount('online_users')->orderBy('server_name', 'asc')->get();

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Server Status';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $language = Lang::all()->pluck('name');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'permission' => $permission,
            'model' => $servers,
        ], 200);
    }

    public function deleteServer(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if(auth()->user()->cannot('manage-vpn-server')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        foreach ($request->id as $id) {
            $server = VpnServer::findorfail($id);
            if ($server->online_users->count() > 0) {
                return response()->json([
                    'message' => 'Server cannot be deleted while users are logged in.',
                ], 403);
            }
        }

        foreach ($request->id as $id) {
            $server = VpnServer::find($id);

            $site_settings = SiteSettings::find(1);
            
            $client = new Client(['base_uri' => 'https://api.cloudflare.com']);
            $response = $client->request('DELETE', "/client/v4/zones/{$site_settings->settings['cf_zone']}/dns_records/{$server->cf_id}",
                ['http_errors' => false, 'headers' => ['X-Auth-Email' => 'mp3sniff@gmail.com', 'X-Auth-Key' => 'ff245b46bd71002891e2890059b122e80b834', 'Content-Type' => 'application/json']]);

            $cloudflare = json_decode($response->getBody());

            if(!$cloudflare->success) {
                return response()->json([
                    'message' => 'Cloudflare: ' . $cloudflare->errors[0]->message,
                ], 403);
            }
        }

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->delete();

        $servers = VpnServer::select('id', 'server_name', 'server_ip', 'server_domain', 'server_access_id', 'limit_bandwidth', 'is_active')->with('server_access')->withCount('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server deleted.',
            'model' => $servers,
        ], 200);
    }

    public function server_status(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if(auth()->user()->cannot('manage-vpn-server')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
            'status' => 'bail|required|in:0,1',
        ]);

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->update(['is_active' => $request->status]);

        $servers = VpnServer::select('id', 'server_name', 'server_ip', 'server_domain', 'server_access_id', 'limit_bandwidth', 'is_active')->with('server_access')->withCount('online_users')->orderBy('server_name', 'asc')->get();

        $msg = ['Server down.', 'Server up.'];

        return response()->json([
            'message' => $msg[$request->status],
            'model' => $servers,
        ], 200);
    }
}
