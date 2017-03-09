<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DistributorController extends Controller
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

        if (Gate::denies('update-account')) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $data = User::where([['distributor', 1], ['status_id', 1], ['credits', '>', 0]])->SearchDistPaginateAndOrder($request);

        $columns = [
            'fullname', 'contact', 'user_group_id', 'credits',
        ];

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
            'columns' => $columns
        ], 200);
    }
}
