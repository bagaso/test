<?php

namespace App\Http\Controllers\ManageUser;

use App\Lang;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ListUserTrashController extends Controller
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

    public function index(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['manage_vpn_server'] = auth()->user()->can('manage-vpn-server');
        $permission['manage_voucher'] = auth()->user()->can('manage-voucher');

        $language = Lang::all()->pluck('name');

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }
        
        $data = User::onlyTrashed()->with('upline', 'status', 'user_group', 'user_package')->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

        $columns = User::$columns;

        $total = User::onlyTrashed()->count();
        $new_users = User::onlyTrashed()->where([['deleted_at', '>=', Carbon::now()->startOfWeek()], ['deleted_at', '<=', Carbon::now()->endOfWeek()]])->count();

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User List : Deleted';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'language' => $language,
            'permission' => $permission,
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
    
    public function restoreUser(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }
        
        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        foreach ($request->id as $id) {
            if (auth()->user()->id == $id) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
        }

        $user = User::onlyTrashed()->whereIn('id', $request->id);
        $user->restore();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        $data = User::onlyTrashed()->with('upline', 'status', 'user_group', 'user_package')->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

        $columns = User::$columns;

        $total = User::onlyTrashed()->count();
        $new_users = User::onlyTrashed()->where([['deleted_at', '>=', Carbon::now()->startOfWeek()], ['deleted_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User restored.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }

    public function forceDeleteUser(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }
        
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        foreach ($request->id as $id) {
            if (auth()->user()->id == $id) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
        }

        $users = User::onlyTrashed()->whereIn('id', $request->id);
        foreach ($users->get() as $user) {
            $user->roles()->detach();
        }
        $users->forceDelete();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        $data = User::onlyTrashed()->with('upline', 'status', 'user_group', 'user_package')->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

        $columns = User::$columns;

        $total = User::onlyTrashed()->count();
        $new_users = User::onlyTrashed()->where([['deleted_at', '>=', Carbon::now()->startOfWeek()], ['deleted_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User deleted permanently.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
}
