<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use App\UserGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserGroupController extends Controller
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

        if (auth()->user()->id == $id || Gate::denies('manage-user')) {
            return response()->json([
                'message' => 'No permission to access this page.',
            ], 403);
        }

        if (Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'No permission update this user.'
            ], 403);
        }

        $user = User::find($id);

        $usergroups = UserGroup::where('id', '>', auth()->user()->user_group->id)->get();

        return response()->json([
            'user_profile' => ['username' => $user->username, 'user_group_id' => $user->user_group_id],
            'usergroups' => $usergroups,
        ], 200);
    }

    public function user_group_client(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findOrFail($id);

        $usergroups = '5';

        if(auth()->user()->user_group->id == 1) {
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
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
        ]);
        
        if ($user->user_group_id <> $request->user_group_id && in_array($request->user_group_id, [3,4,5])) {
            $user->roles()->detach();
        }
        $user->user_group_id = $request->user_group_id;

        $user->save();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }

        if(auth()->user()->isAdmin() || auth()->user()->isSubAdmin()) {
            $data = User::with('status', 'user_group', 'user_package')->where('user_group_id', 5)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::with('status', 'user_group', 'user_package')->where([['parent_id', auth()->user()->id], ['user_group_id', 5]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        return response()->json([
            'message' => 'User group updated.',
            'user_profile' => ['user_group_id' => $user->user_group_id],
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function user_group_reseller(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findOrFail($id);

        $usergroups = '5';

        if(auth()->user()->user_group->id == 1) {
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
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
        ]);
        
        if ($user->user_group_id <> $request->user_group_id && in_array($request->user_group_id, [3,4,5])) {
            $user->roles()->detach();
        }
        $user->user_group_id = $request->user_group_id;

        $user->save();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }

        if(auth()->user()->isAdmin() || auth()->user()->isSubAdmin()) {
            $data = User::with('status', 'user_group', 'user_package')->where('user_group_id', 4)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::with('status', 'user_group', 'user_package')->where([['parent_id', auth()->user()->id], ['user_group_id', 4]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        return response()->json([
            'message' => 'User group updated.',
            'user_profile' => ['user_group_id' => $user->user_group_id],
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function user_group_premium(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findOrFail($id);

        $usergroups = '5';

        if(auth()->user()->user_group->id == 1) {
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
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
        ]);
        
        if ($user->user_group_id <> $request->user_group_id && in_array($request->user_group_id, [3,4,5])) {
            $user->roles()->detach();
        }
        $user->user_group_id = $request->user_group_id;

        $user->save();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }

        if(auth()->user()->isAdmin() || auth()->user()->isSubAdmin()) {
            $data = User::with('status', 'user_group', 'user_package')->where('user_group_id', 3)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::with('status', 'user_group', 'user_package')->where([['parent_id', auth()->user()->id], ['user_group_id', 3]])->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;

        return response()->json([
            'message' => 'User group updated.',
            'user_profile' => ['user_group_id' => $user->user_group_id],
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function user_group_ultimate(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findOrFail($id);

        $usergroups = '5';

        if(auth()->user()->user_group->id == 1) {
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
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
        ]);
        
        if ($user->user_group_id <> $request->user_group_id && in_array($request->user_group_id, [3,4,5])) {
            $user->roles()->detach();
        }
        $user->user_group_id = $request->user_group_id;

        $user->save();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }

        $data = User::with('upline', 'status', 'user_group', 'user_package')->where('user_group_id', '=', 2)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);

        $columns = User::$columns;

        return response()->json([
            'message' => 'User group updated.',
            'user_profile' => ['user_group_id' => $user->user_group_id],
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function user_group_all(Request $request, $id)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $user = User::findOrFail($id);

        $usergroups = '5';

        if(auth()->user()->user_group->id == 1) {
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
            'user_group_id' => 'bail|required|integer|in:' . $usergroups,
        ]);
        
        if ($user->user_group_id <> $request->user_group_id && in_array($request->user_group_id, [3,4,5])) {
            $user->roles()->detach();
        }
        $user->user_group_id = $request->user_group_id;

        $user->save();

        $status_id = [$request->status_id];
        if($request->status_id == -1) {
            $status_id = [1,2,3];
        }

        if(auth()->user()->isAdmin() || auth()->user()->isSubAdmin()) {
            $data = User::with('upline', 'status', 'user_group', 'user_package')->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        } else {
            $data = User::with('status', 'user_group', 'user_package')->where('parent_id', '=', auth()->user()->id)->whereIn('status_id', $status_id)->SearchPaginateAndOrder($request);
        }

        $columns = User::$columns;
        

        return response()->json([
            'message' => 'User group updated.',
            'user_profile' => ['user_group_id' => $user->user_group_id],
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }
}
