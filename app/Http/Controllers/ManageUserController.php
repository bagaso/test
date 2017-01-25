<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ManageUserController extends Controller
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
    
    public function allUser(Request $request)
    {
        if (Gate::denies('manage-user')) {
            return response()->json(['message' => 'No permission to manage user.'], 403);
        }
        
        $account = $request->user();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if($account->isAdmin()) {
            $data = User::whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        } else {
            $data = User::where('parent_id', '=', $account->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        }

        return response()->json([
            'model' => $data,
            'columns' => $columns
        ]);
    }

    public function ultimateUser(Request $request)
    {
        if (Gate::denies('manage-user')) {
            return response()->json(['message' => 'No permission to manage user.'], 403);
        }

        $account = $request->user();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if($account->isAdmin()) {
            $data = User::where('user_group_id', '=', 2)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        } else {
            $data = User::where([['parent_id', '=', $account->id], ['user_group_id', '=', 2]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        }

        return response()->json([
            'model' => $data,
            'columns' => $columns
        ]);
    }

    public function premiumUser(Request $request)
    {
        if (Gate::denies('manage-user')) {
            return response()->json(['message' => 'No permission to manage user.'], 403);
        }

        $account = $request->user();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if($account->isAdmin()) {
            $data = User::where('user_group_id', '=', 3)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        } else {
            $data = User::where([['parent_id', '=', $account->id], ['user_group_id', '=', 3]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        }

        return response()->json([
            'model' => $data,
            'columns' => $columns
        ]);
    }

    public function resellerUser(Request $request)
    {
        if (Gate::denies('manage-user')) {
            return response()->json(['message' => 'No permission to manage user.'], 403);
        }

        $account = $request->user();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if($account->isAdmin()) {
            $data = User::where('user_group_id', '=', 4)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        } else {
            $data = User::where([['parent_id', '=', $account->id], ['user_group_id', '=', 4]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        }

        return response()->json([
            'model' => $data,
            'columns' => $columns
        ]);
    }

    public function clientUser(Request $request)
    {
        if (Gate::denies('manage-user')) {
            return response()->json(['message' => 'No permission to manage user.'], 403);
        }

        $account = $request->user();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [0,1,2];
        }

        if($account->isAdmin()) {
            $data = User::where('user_group_id', '=', 5)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        } else {
            $data = User::where([['parent_id', '=', $account->id], ['user_group_id', '=', 5]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
            $columns = User::$columns;
        }

        return response()->json([
            'model' => $data,
            'columns' => $columns
        ]);
    }

    public function viewCreate()
    {
        if (Gate::denies('create-user')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'permission' => array('set_user_usergroup' => Gate::allows('set-user-usergroup'),
                                  'set_user_status' => Gate::allows('set-user-status')
            )
        ]);
    }

    public function create(Request $request)
    {
        if (Gate::denies('create-user')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if($request->user()->user_group_id == 1) {
            $usergroups = '2,3,4,5';
        }
        if($request->user()->user_group_id == 2) {
            $usergroups = '3,4,5';
        }
        if($request->user()->user_group_id == 3) {
            $usergroups = '4,5';
        }
        if($request->user()->user_group_id == 4) {
            $usergroups = '5';
        }

        $this->validate($request, [
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
            'username' => 'bail|required|alpha_num|between:6,20|unique:users,username',
            'password' => 'bail|required|between:6,15|confirmed',
            'password_confirmation' => 'bail|required|between:6,15',
            'email' => 'bail|required|email|max:50|unique:users,email',
            'fullname' => 'bail|required|max:50',
            'status_id' => 'bail|required|integer|in:0,1,2',
        ]);

        $new_user = new User();
        $new_user->create($request->all());
        return response()->json(['message' => 'success'], 200);
    }

    public function viewProfile($id)
    {
        if (Gate::denies('update-user-profile', $id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::findOrFail($id);
        return response()->json([
            'permission' => array('update_user_profile' => Gate::allows('update-user-profile', $id),
                                  'update_user_security' => Gate::allows('update-user-profile', $id) && Gate::allows('update-user-security', $id),
                                  'update_user_usergroup' => Gate::allows('update-user-profile', $id) && Gate::allows('update-user-usergroup', $id),
                                  'update_user_status' => Gate::allows('update-user-profile', $id) && Gate::allows('update-user-status', $id)
                                ),
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        if (Gate::denies('update-user-profile', $id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        
        $user = User::findOrFail($id);

        if($request->user()->user_group_id == 1) {
            $usergroups = '2,3,4,5';
        }
        if($request->user()->user_group_id == 2) {
            $usergroups = '3,4,5';
        }
        if($request->user()->user_group_id == 3) {
            $usergroups = '4,5';
        }
        if($request->user()->user_group_id == 4) {
            $usergroups = '5';
        }

        $this->validate($request, [
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
            'username' => 'bail|required|alpha_num|between:6,20|unique:users,username,' . $user->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $user->id,
            'fullname' => 'bail|required|max:50',
            'status_id' => 'bail|required|integer|in:0,1,2',
        ]);

        if (Gate::allows('update-user-usergroup', $id)) {
            $user->user_group_id = $request->user_group_id;
        }
        if (Gate::allows('update-user-status', $id)) {
            $user->status_id = $request->status_id;
        }

        if ($request->user()->isAdmin()) {
            $user->username = $request->username;
        }
        $user->email = $request->email;
        $user->fullname = $request->fullname;

        $user->save();

        return response()->json([
            'user' => $user
        ], 200);
    }

    public function updateSecurity(Request $request, $id)
    {
        if (Gate::denies('update-user-profile', $id) || Gate::denies('update-user-security', $id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        
        $user = User::findorfail($id);

        $this->validate($request, [
            'new_password' => 'bail|required|between:6,15|confirmed',
            'new_password_confirmation' => 'bail|required|between:6,15',
        ]);

        $user->password = $request->new_password;

        return response()->json('', $user->save() ? 200 : 500);

    } // function update
        
}
