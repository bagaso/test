<?php

namespace App\Http\Controllers\ManageUser;

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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['create_user'] = auth()->user()->can('create-user');

        if (Gate::denies('manage-user') || !in_array(auth()->user()->user_group_id, [1,2,3])) {
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
            $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', '<>', 1)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = User::where([['user_group_id', '<>', 1], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        $permission['delete_user'] = auth()->user()->isAdmin() || in_array('PCODE_005', json_decode(auth()->user()->roles->pluck('code')));
        $permission['update_user_group'] = auth()->user()->isAdmin() || in_array('PCODE_011', json_decode(auth()->user()->roles->pluck('code')));
        $permission['update_user_status'] =  auth()->user()->isAdmin() || in_array('PCODE_005', json_decode(auth()->user()->roles->pluck('code')));
        $permission['update_user_package'] =  auth()->user()->isAdmin() || in_array('PCODE_018', json_decode(auth()->user()->roles->pluck('code')));
        
        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }

    public function updateUserGroup(Request $request)
    {
        if(auth()->user()->user_group_id == 1) {
            $usergroups = '2,3,4,5';
        }
        if(auth()->user()->user_group_id == 2) {
            $usergroups = '3,4,5';
        }
        if(auth()->user()->user_group_id == 3) {
            $usergroups = '4,5';
        }
        if(auth()->user()->user_group_id == 4) {
            $usergroups = '5';
        }

        $this->validate($request, [
            'id' => 'bail|required|array',
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
        ]);

        foreach ($request->id as $id) {
            if (auth()->user()->id == $id || Gate::denies('update-user-group', $id)) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
        }

        $users = User::whereIn('id', $request->id);
        $users->update(['user_group_id' => $request->user_group_id]);

        foreach ($request->id as $id) {
            $user = User::findorfail($id);
            if ($user->user_group_id <> $request->user_group_id && in_array($request->user_group_id, [2,3,4])) {
                $user->roles()->sync([1,2,4,6,13,15,16,18]);
            }
            if ($user->user_group_id <> $request->user_group_id && $request->user_group_id == 5) {
                $user->roles()->detach();
            }
        }

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if(auth()->user()->isAdmin()) {
            $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', '<>', 1)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = User::where([['user_group_id', '<>', 1], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User status updated.',
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
            $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where('parent_id', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', '<>', 1)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = User::where([['user_group_id', '<>', 1], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

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
            $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', '<>', 1)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = User::where([['user_group_id', '<>', 1], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User deleted.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }

    public function updateUserPackage(Request $request)
    {
        $this->validate($request, [
            'id' => 'bail|required|array',
            'package' => 'bail|required|integer|in:1,3,4',
        ]);

        foreach ($request->id as $id) {
            if (auth()->user()->id == $id || Gate::denies('update-user-package', $id)) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
        }

        foreach ($request->id as $id)
        {
            $user = User::findorfail($id);

            $current_copy = Carbon::now();
            $expired_at = Carbon::parse($user->getOriginal('expired_at'));

            if(!auth()->user()->isAdmin()) {
                $bronze_days_left = $current_copy->diffInSeconds($expired_at);
                if($user->vpn_session == 3) {
                    $bronze_days_left = $current_copy->diffInSeconds($expired_at) * 2;
                }
                if($user->vpn_session == 4) {
                    $bronze_days_left = $current_copy->diffInSeconds($expired_at) * 3;
                }
                $dt = $current_copy->addSeconds($bronze_days_left);
                if($request->package > 1 && $dt->diffInDays() < 30) {

                    $status_id = [$request->status_id];
                    if($request->status_id == -1) {
                        $status_id = [0,1,2];
                    }

                    if(auth()->user()->isAdmin()) {
                        $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
                    } else {
                        $data = User::where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
                    }

                    $columns = User::$columns;

                    $total = auth()->user()->isAdmin() ? User::where('user_group_id', '<>', 1)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
                    $new_users = User::where([['user_group_id', '<>', 1], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

                    return response()->json([
                        'message' => 'User must have 30 days of remaining duration.',
                        'model' => $data,
                        'total' => $total,
                        'new_users' => $new_users,
                        'columns' => $columns,
                    ], 403);
                }
            }

            $current = Carbon::now();

            if($user->vpn_session <> $request->package && $current->lt($expired_at)) {
                $add = 0;
                if($user->vpn_session == 1) {
                    if($request->package == 3) {
                        $add = $current->diffInSeconds($expired_at) / 2;
                    }
                    if($request->package == 4) {
                        $add = $current->diffInSeconds($expired_at) / 3;
                    }
                }
                if($user->vpn_session == 3) {
                    if($request->package == 1) {
                        $add = $current->diffInSeconds($expired_at) * 2;
                    }
                    if($request->package == 4) {
                        $add = ($current->diffInSeconds($expired_at) * 2) / 3;
                    }
                }
                if($user->vpn_session == 4) {
                    if($request->package == 1) {
                        $add = $current->diffInSeconds($expired_at) * 3;
                    }
                    if($request->package == 3) {
                        $add = ($current->diffInSeconds($expired_at) * 3) / 2;
                    }
                }
                $user->expired_at = $current->addSeconds($add);
            }
            $user->vpn_session = $request->package;
            $user->save();
        }

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if(auth()->user()->isAdmin()) {
            $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        $total = auth()->user()->isAdmin() ? User::where('user_group_id', '<>', 1)->count() : User::where([['parent_id', '=', auth()->user()->id], ['user_group_id', '>', auth()->user()->user_group_id]])->count();
        $new_users = User::where([['user_group_id', '<>', 1], ['created_at', '>=', Carbon::now()->startOfWeek()], ['created_at', '<=', Carbon::now()->endOfWeek()]])->count();

        return response()->json([
            'message' => 'User package updated.',
            'model' => $data,
            'total' => $total,
            'new_users' => $new_users,
            'columns' => $columns,
        ], 200);
    }
}
