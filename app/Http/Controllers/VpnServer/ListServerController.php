<?php

namespace App\Http\Controllers\VpnServer;

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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');
        
        if(!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }
        
        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();
        
        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $servers,
        ], 200);
    }

    public function deleteServer(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
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
            $client = new Client(['base_uri' => 'https://api.cloudflare.com']);
            $response = $client->request('DELETE', '/client/v4/zones/5e777546f7645f3243d2290ca7b9c5af/dns_records/' . $server->cf_id,
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

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server deleted.',
            'model' => $servers,
        ], 200);
    }

    public function server_status(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
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

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        $msg = ['Server down.', 'Server up.'];

        return response()->json([
            'message' => $msg[$request->status],
            'model' => $servers,
        ], 200);
    }

    public function server_access(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
            'access' => 'bail|required|in:0,1,2',
        ]);

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->update(['access' => $request->access]);

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        $msg = ['Server set to free', 'Server set to premium', 'Server set to vip'];

        return response()->json([
            'message' => $msg[$request->access],
            'model' => $servers,
        ], 200);
    }
}
