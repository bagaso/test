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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (auth()->user()->id == $id || !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'user_profile' => $user
        ], 200);
    }

    public function updateDuration(Request $request, $id)
    {
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
