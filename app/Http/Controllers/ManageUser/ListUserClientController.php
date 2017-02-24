<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class ListUserClientController extends Controller
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

    public function index(Request $request)
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['create_user'] = auth()->user()->can('create-user');

        if (Gate::denies('manage-user') || !in_array(auth()->user()->user_group_id, [1,2,3,4])) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if(auth()->user()->isAdmin()) {
            $data = User::where('user_group_id', 5)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where([['parent_id', auth()->user()->id], ['user_group_id', 5]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', 5)->count() : User::where([['user_group_id', 5], ['parent_id', auth()->user()->id]])->count();

        $new_users = auth()->user()->isAdmin() ? User::where([['user_group_id', 5], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count() : User::where([['user_group_id', 5], ['parent_id', auth()->user()->id], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        $permission['delete_user'] = auth()->user()->isAdmin() || in_array('PCODE_005', json_decode(auth()->user()->roles->pluck('code')));
        $permission['update_user_group'] = auth()->user()->isAdmin() || in_array('PCODE_011', json_decode(auth()->user()->roles->pluck('code')));
        $permission['update_user_status'] =  auth()->user()->isAdmin() || in_array('PCODE_005', json_decode(auth()->user()->roles->pluck('code')));

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
    
    public function updateUserStatus(Request $request)
    {
        $this->validate($request, [
            'id' => 'bail|required|array',
            'status_id_set' => 'bail|required|integer|in:0,1,2',
        ]);

        foreach ($request->id as $id) {
            if (auth()->user()->id == $id || Gate::denies('update-user-status', $id)) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
        }

        $users = User::whereIn('id', $request->id);
        $users->update(['status_id' => $request->status_id_set]);

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if(auth()->user()->isAdmin()) {
            $data = User::where('user_group_id', 5)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where([['parent_id', auth()->user()->id], ['user_group_id', 5]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', 5)->count() : User::where([['user_group_id', 5], ['parent_id', auth()->user()->id]])->count();
        $new_users = auth()->user()->isAdmin() ? User::where([['user_group_id', 5], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count() : User::where([['user_group_id', 5], ['parent_id', auth()->user()->id], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User status updated.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }

    public function deleteUsers(Request $request)
    {
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
            $status_id = [0,1,2];
        }

        if(auth()->user()->isAdmin()) {
            $data = User::where('user_group_id', 5)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where([['parent_id', auth()->user()->id], ['user_group_id', 5]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', 5)->count() : User::where([['user_group_id', 5], ['parent_id', auth()->user()->id]])->count();
        $new_users = auth()->user()->isAdmin() ? User::where([['user_group_id', 5], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count() : User::where([['user_group_id', 5], ['parent_id', auth()->user()->id], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User deleted.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
}
