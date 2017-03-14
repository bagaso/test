<?php

namespace App\Http\Controllers\ManageUser;

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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['create_user'] = auth()->user()->can('create-user');

        if (!auth()->user()->isAdmin()) {
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
        
        $data = User::onlyTrashed()->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

        $columns = User::$columns;

        $total = User::onlyTrashed()->count();
        $new_users = User::onlyTrashed()->where([['deleted_at', '>=', Carbon::now()->startOfWeek()], ['deleted_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
    
    public function restoreUser(Request $request)
    {
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

        $data = User::onlyTrashed()->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

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

        $data = User::onlyTrashed()->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

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
