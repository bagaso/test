<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (auth()->user()->id == $id || !auth()->user()->isAdmin()) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User Duration';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['create_user'] = auth()->user()->can('create-user');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'permission' => $permission,
            'user_profile' => $user
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
        if(auth()->user()->id == $id || !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }

        $this->validate($request, [
            'days' => 'bail|required|integer|between:-30,30',
            'hours' => 'bail|required|integer|between:-24,24'
        ]);

        $current = Carbon::now();
        $dt = Carbon::parse($user->getOriginal('expired_at'));
        if($current->lt($dt)) {
            $user->expired_at = $dt->addDays($request->days)->addHours($request->hours);
        } else {
            $user->expired_at = $current->addDays($request->days)->addHours($request->hours);
        }

        $user->save();
        return response()->json([
            'message' => 'User duration updated.',
            'user_profile' => $user
        ], 200);
    }
}
