<?php

namespace App\Http\Controllers\VpnServer;

use App\VpnServer;
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

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->delete();

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server deleted.',
            'model' => $servers,
        ], 200);
    }

    public function upServer(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->update(['is_active' => 1]);

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server up.',
            'model' => $servers,
        ], 200);
    }

    public function downServer(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->update(['is_active' => 0]);

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server down.',
            'model' => $servers,
        ], 200);
    }

    public function freeServer(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->update(['free_user' => 1]);

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server set to Free',
            'model' => $servers,
        ], 200);
    }

    public function premiumServer(Request $request)
    {
        if(!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        $servers = VpnServer::whereIn('id', $request->id);
        $servers->update(['free_user' => 0]);

        $servers = VpnServer::with('online_users')->orderBy('server_name', 'asc')->get();

        return response()->json([
            'message' => 'Server set to Premium',
            'model' => $servers,
        ], 200);
    }
}
