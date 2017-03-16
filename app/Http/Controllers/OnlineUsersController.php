<?php

namespace App\Http\Controllers;

use App\Jobs\JobVpnDisconnectUser;
use App\OnlineUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OnlineUsersController extends Controller
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

        if (Gate::denies('update-account')) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $data = OnlineUser::with('user', 'vpnserver')->orderBy('vpn_server_id', 'asc')->paginate(50);

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
        ], 200);
    }

    public function searchOnlineUser(Request $request)
    {
        $data = OnlineUser::with(['user', 'vpnserver'])->whereHas('user', function($query) use ($request) {
            if($request->has('search_input')) {
                $query->where('username', 'LIKE', '%'.trim($request->search_input).'%');
            }
        })->orderBy('vpn_server_id', 'asc')->paginate(50);
        
        return response()->json([
            'model' => $data,
        ], 200);
    }

    public function disconnectVpn(Request $request)
    {
        try {
            if (!auth()->user()->isAdmin() || auth()->user()->id == $request->user_id) {
                return response()->json(['message' => 'Action not allowed.'], 403);
            }

            $server_id = $request->server_id;
            $vpn_user = OnlineUser::with(['vpnserver', 'user'])->where([['user_id', $request->user_id], ['vpn_server_id', $server_id]])->firstorfail();
            $job = (new JobVpnDisconnectUser($vpn_user->user->username, $vpn_user->vpnserver->server_ip, $vpn_user->vpnserver->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
            dispatch($job);
            return response()->json(['message' => 'Request sent to the server.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json(['message' => 'Session not found.'], 404);
        }
    }
}
