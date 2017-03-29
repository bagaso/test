<?php

namespace App\Http\Controllers\VpnServer;

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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $server_info = VpnServer::findOrFail($id);

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'server_info' => $server_info,
        ], 200);
    }

    public function updateServer(Request $request, $id)
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $server = VpnServer::findorfail($id);

        $this->validate($request, [
            'server_name' => 'bail|required|unique:vpn_servers,server_name,' . $server->id,
            'server_ip' => 'bail|required|ip|unique:vpn_servers,server_ip,' . $server->id,
            'server_domain' => 'bail|required|unique:vpn_servers,server_domain,' . $server->id,
            'server_key' => 'bail|required|unique:vpn_servers,server_key,' . $server->id,
            'server_access' => 'bail|required|in:0,1,2',
            'server_status' => 'bail|required|boolean',
            'package_bronze' => 'bail|required|boolean',
            'package_silver' => 'bail|required|boolean',
            'package_gold' => 'bail|required|boolean',
            'limit_bandwidth' => 'bail|required|boolean',
        ]);

        if($server->online_users->count() > 0 && $server->server_ip <> $request->server_ip) {
            return response()->json([
                'message' => 'Server IP cannot be change at this moment.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $client = new Client(['base_uri' => 'https://api.cloudflare.com']);
        $response = $client->request('PUT', '/client/v4/zones/a527d7548c3b90128494ec9ff2837d8a/dns_records/' . $server->cf_id,
            ['http_errors' => false, 'headers' => ['X-Auth-Email' => 'mp3sniff@gmail.com', 'X-Auth-Key' => 'ff245b46bd71002891e2890059b122e80b834', 'Content-Type' => 'application/json'], 'json' => ['type' => 'A', 'name' => $request->server_domain, 'content' => $request->server_ip]]);

        $cloudflare = json_decode($response->getBody());

        if(!$cloudflare->success) {
            return response()->json([
                'message' => 'Cloudflare: ' . $cloudflare->errors[0]->message,
            ], 403);
        }

        $allowed_userpackage['bronze'] = (int)$request->package_bronze;
        $allowed_userpackage['silver'] = (int)$request->package_silver;
        $allowed_userpackage['gold'] = (int)$request->package_gold;

        $server->server_name = $request->server_name;
        $server->server_ip = $request->server_ip;
        $server->server_domain = $request->server_domain;
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
