<?php

namespace App\Http\Controllers\VpnServer;

use App\VpnServer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

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

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
        ], 200);
    }

    public function addServer(Request $request)
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

        $this->validate($request, [
            'server_name' => 'bail|required|unique:vpn_servers,server_name',
            'server_ip' => 'bail|required|ip|unique:vpn_servers,server_ip',
            'server_domain' => 'bail|required|unique:vpn_servers,server_domain',
            'server_key' => 'bail|required|unique:vpn_servers,server_key',
            'vpn_secret' => 'required',
            'server_port' => 'bail|required|integer',
            'server_access' => 'bail|required|boolean',
            'server_status' => 'bail|required|boolean',
        ]);

        $cloud_flare_email = 'mp3sniff@gmail.com';
        $cloud_flare_api = 'ff245b46bd71002891e2890059b122e80b834';
        $cloud_flare_zone = '5e777546f7645f3243d2290ca7b9c5af';

        $client = new Client();

        //$client->setDefaultOption('headers', array('X-Auth-Email' => $cloud_flare_email, 'X-Auth-Key' => $cloud_flare_api));

        $response = $client->request('POST', 'https://api.cloudflare.com/client/v4/zones/' . $cloud_flare_zone . '/dns_records',
            ['form_params' => ['type' => 'A', 'name' => $request->server_domain, 'content' => $request->server_ip]]);

        if(!$response->success) {
            return response()->json([
                'message' => $response->errors->message,
            ], 403);
        }

        $server = new VpnServer;

        $server->server_name = $request->server_name;
        $server->server_ip = $request->server_ip;
        $server->server_domain = $request->server_domain;
        $server->server_key = $request->server_key;
        $server->server_port = $request->server_port;
        $server->vpn_secret = $request->vpn_secret;
        $server->free_user = $request->server_access;
        $server->is_active = $request->server_status;
        //$server->save();

        return response()->json([
            'message' => 'New server added.',
        ], 200);
    }
}
