<?php

namespace App\Http\Controllers\Account;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class TransferCreditsController extends Controller
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

    public function index()
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if(!auth()->user()->can('manage-user')) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        if(!auth()->user()->isAdmin() && !auth()->user()->distributor) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission
        ], 200);
    }

    public function transfer(Request $request)
    {

        if(!auth()->user()->distributor && Gate::denies('manage_user')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'username' => 'required',
            'credits' => 'bail|required|integer|between:1,10',
        ]);

        if (!auth()->user()->isAdmin() && auth()->user()->credits < $request->credits) {
            return response()->json([
                'message' => 'Input must be lower or equal to your available credits.',
            ], 403);
        }

        try {
            $user = User::where('username', $request->username)->firstorfail();

            if($user->isAdmin() || auth()->user()->username == $user->username) {
                return response()->json([
                    'message' => 'Action not allowed.',
                ], 403);
            }
            DB::transaction(function () use ($request) {
                $user = User::where('username', $request->username)->firstorfail();
                if (!auth()->user()->isAdmin()) {
                    $request->user()->credits -= $request->credits;
                    $request->user()->save();
                }
                $user->credits += $request->credits;
                $user->save();
            }, 5);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json([
                'message' => 'User not found.',
            ], 403);
        }



        $withs = $request->credits > 1 ? ' credits' : ' credit';
        return response()->json([
            'message' => 'You have transferred ' . $request->credits . $withs . ' to ' . $request->username . '.',
            'profile' => auth()->user(),
        ], 200);

    }
}
