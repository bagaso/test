<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserCreditController extends Controller
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
                'message' => 'No permission to update this user.',
            ], 403);
        }

        $user = User::findOrFail($id);
        
        return response()->json([
            'profile' => ['credits' => auth()->user()->credits],
            'user_profile' => ['username' => $user->username, 'credits' => $user->credits, 'expired_at' => $user->expired_at]
        ], 200);
    }

    public function updateCredits(Request $request, $id)
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

        $this->validate($request, [
            'top_up' => 'bail|required|boolean',
        ]);

        $user = User::with('user_package')->findOrFail($id);

        if ($user->can('unlimited-credits')) {
            return response()->json([
                'message' => 'User cannot received anymore credits.',
            ], 403);
        }

        if ($request->top_up) {
            if(auth()->user()->isAdmin() || in_array(auth()->user()->user_group->id, [2])) {
                $this->validate($request, [
                    'input_credits' => 'bail|required|integer|min:1|max:' . $user->user_package->user_package['max_credit'] . '',
                ]);
            } else {
                $this->validate($request, [
                    'input_credits' => 'bail|required|integer|min:' . $user->user_package->user_package['min_credit'] . '|max:' . $user->user_package->user_package['max_credit'] . '',
                ]);
            }
        } else {
            if(auth()->user()->can('minus-credits')) {
                $this->validate($request, [
                    'input_credits' => 'bail|required|integer|between:-20,' . $db_settings->settings['max_transfer_credits'],
                ]);
            } else {
                $this->validate($request, [
                    'input_credits' => 'bail|required|integer|between:1,' . $db_settings->settings['max_transfer_credits'],
                ]);
            }

        }

        if (auth()->user()->cannot('unlimited-credits') && auth()->user()->credits < $request->input_credits) {
            return response()->json([
                'message' => 'Input must be lower or equal to your available credits.',
            ], 403);
        }

        DB::transaction(function () use ($request, $id) {

            $user = User::with('user_package')->findOrFail($id);
            $account = User::findorfail(auth()->user()->id);

            if ($request->top_up) {
                $current = Carbon::now();
                $expired_at = Carbon::parse($user->getOriginal('expired_at'));
                if($current->lt($expired_at)) {
                    $user->expired_at = $expired_at->addSeconds((2595600 * $request->input_credits) / intval($user->user_package->user_package['cost']));
                } else {
                    $user->expired_at = $current->addSeconds((2595600 * $request->input_credits) / intval($user->user_package->user_package['cost']));
                }
            } else {
                if ($request->input_credits < 0 && ($user->getOriginal('credits') + $request->input_credits) < 0) {
                    return response()->json(['message' => 'User credits must be a non-negative value.'], 403);
                }
                $user->credits += $request->input_credits;
            }

            $user->save();

            if (auth()->user()->cannot('unlimited-credits')) {
                $account->credits -= $request->input_credits;
                $account->save();
            }

        }, 5);

        if ($request->top_up) {
            $message = 'User has been successfully top-up.';
        } else {
            $message = 'Credits has been transferred successfully.';
        }

        $user = User::findOrFail($id);
        $account = User::findorfail(auth()->user()->id);

        return response()->json([
            'message' => $message,
            'profile' => ['credits' => $account->credits],
            'user_profile' => ['credits' => $user->credits, 'expired_at' => $user->expired_at]
        ], 200);
    }
}
