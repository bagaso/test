<?php

namespace App\Http\Controllers\ManageUser;

use App\Lang;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class ListUserAllController extends Controller
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
        $permission['manage_update_json'] = auth()->user()->can('manage-update-json');

        $language = Lang::all()->pluck('name');

        if (Gate::denies('manage-user') || !in_array(auth()->user()->user_group_id, [1,2,3])) {
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

        if(auth()->user()->can('manage-all-users')) {
            $data = User::with('upline', 'status', 'user_group', 'user_package')->where('user_group_id', '>', 2)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::with('status', 'user_group', 'user_package')->where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->can('manage-all-users') ? User::where('user_group_id', '>', 2)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = auth()->user()->can('manage-all-users') ? User::where([['user_group_id', '>', 2], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count() : User::where([['user_group_id', '>', 2], ['parent_id', auth()->user()->id], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();
        $active_duration = auth()->user()->can('manage-all-users') ? User::where([['user_group_id', '>', 2], ['expired_at', '>=', \Carbon\Carbon::now()]])->count() : User::where([['user_group_id', '>', 2], ['parent_id', auth()->user()->id], ['expired_at', '>=', \Carbon\Carbon::now()]])->count();

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User List : All';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['access_duration'] = auth()->user()->isAdmin() || auth()->user()->isSubAdmin();
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'language' => $language,
            'permission' => $permission,
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
            'active_duration' => $active_duration,
        ], 200);
    }

    public function deleteUsers(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        foreach ($request->id as $id) {
            if (auth()->user()->id == $id || Gate::denies('delete-user', $id)) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
        }

        $users = User::whereIn('id', $request->id);
        $users->delete();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }

        if(auth()->user()->can('manage-all-users')) {
            $data = User::with('upline', 'status', 'user_group', 'user_package')->where('user_group_id', '>', 2)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::with('status', 'user_group', 'user_package')->where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->can('manage-all-users') ? User::where('user_group_id', '>', 2)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = auth()->user()->can('manage-all-users') ? User::where([['user_group_id', '>', 2], ['parent_id', auth()->user()->id], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count() : User::where([['user_group_id', '>', 2], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User deleted.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
}
