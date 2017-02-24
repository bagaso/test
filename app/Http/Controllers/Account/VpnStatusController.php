<?php

namespace App\Http\Controllers\Account;

use App\Jobs\JobVpnDisconnectUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VpnStatusController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth:api']);

    } // function __construct

    public function index()
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'vpn_session' => \App\OnlineUser::with('vpnserver')->where('user_id', auth()->user()->id)->get(),
        ], 200);
    }

    public function disconnect(Request $request)
    {
        try {
            $server_id = $request->server_id;
            $vpn_user = \App\OnlineUser::with('vpnserver')->where([['user_id', auth()->user()->id], ['vpn_server_id',$server_id]])->firstorfail();
            $job = (new JobVpnDisconnectUser(auth()->user()->username, $vpn_user->vpnserver->server_ip, $vpn_user->vpnserver->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
            dispatch($job);
            return response()->json(['message' => 'Request sent to the server.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json(['message' => 'Session not found.'], 404);
        }
    }
}
