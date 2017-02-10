<?php

namespace App\Http\Controllers;

use App\Jobs\JobVpnDisconnectUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class UpdateAccount extends Controller
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

    public function update(Request $request)
    {
        // $this->authorize('update-account');

        if (Gate::denies('update-account')) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }

        $account = $request->user();

        $this->validate($request, [
            'username' => 'sometimes|bail|required|alpha_num|between:6,20|unique:users,username,' . $account->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $account->id,
            'fullname' => 'bail|required|max:50',
        ]);

        if (auth()->user()->isAdmin()) {
            $account->username = $request->username;
        }
        $account->email = $request->email;
        $account->fullname = $request->fullname;
        $account->save();
        
        return response()->json([
            'message' => 'Profile updated successfully.',
            'account' => ['updated_at' => $account->updated_at]
        ], 200);
        
    } // function update
    
    public function disconnectVpn(Request $request)
    {
        try {
            $server_id = $request->server_id;
            $server = \App\VpnServer::findorfail($server_id);
            $job = (new JobVpnDisconnectUser(auth()->user()->username, $server->server_ip, $server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
            dispatch($job);
            return response()->json(['message' => 'Request sent to the server.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json(['message' => 'Server not found.'], 404);
        }
    }

} // end class
