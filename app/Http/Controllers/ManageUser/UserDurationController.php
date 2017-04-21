<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserDurationController extends Controller
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

        if (auth()->user()->id == $id || (!auth()->user()->isAdmin() && !auth()->user()->isSubAdmin())) {
            return response()->json([
                'message' => 'No permission to access this page.',
            ], 403);
        }

        if (Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'No permission to update this user.',
            ], 403);
        }

        if (Gate::denies('update-user-duration', $id)) {
            return response()->json([
                'message' => 'No permission to update user duration.',
            ], 403);
        }

        $user = User::findOrFail($id);

        return response()->json([
            'user_profile' => ['username' => $user->username, 'expired_at' => $user->expired_at]
        ], 200);
    }

    public function updateDuration(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        
        $user = User::findorfail($id);
        if(auth()->user()->id == $id || Gate::denies('update-user', $id) || Gate::denies('update-user-duration', $id)) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }

        $this->validate($request, [
            'input_days' => 'bail|required|integer|between:-30,30',
            'input_hours' => 'bail|required|integer|between:-24,24'
        ]);

        $current = Carbon::now();
        $dt = Carbon::parse($user->getOriginal('expired_at'));
        if($current->lt($dt)) {
            $user->expired_at = $dt->addDays($request->input_days)->addHours($request->input_hours);
        } else {
            $user->expired_at = $current->addDays($request->input_days)->addHours($request->input_hours);
        }

        $user->save();
        return response()->json([
            'message' => 'User duration updated.',
            'user_profile' => ['expired_at' => $user->expired_at]
        ], 200);
    }
}
