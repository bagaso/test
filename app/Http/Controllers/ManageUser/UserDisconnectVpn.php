<?php

namespace App\Http\Controllers\ManageUser;

use App\Jobs\JobVpnDisconnectUser;
use App\SiteSettings;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserDisconnectVpn extends Controller
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
    
    public function index(Request $request, $id)
    {
        try {
            if (auth()->user()->id == $id || !auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Action not allowed.'], 403);
            }

            $db_settings = SiteSettings::find(1);
            $server_id = $request->server_id;
            $vpn_user = \App\OnlineUser::with(['vpnserver', 'user'])->where([['user_id', $id], ['vpn_server_id', $server_id]])->firstorfail();
            $job = (new JobVpnDisconnectUser($vpn_user->user->username, $vpn_user->vpnserver->server_ip, $vpn_user->vpnserver->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
            dispatch($job);
            return response()->json(['message' => 'Request sent to the server.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json(['message' => 'Session not found.'], 404);
        }
    }
}
