<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (auth()->user()->id == $id || Gate::denies('manage-user')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('update-user-profile', $id)) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to update user profile.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }
        if (Gate::denies('transfer-credits', $id)) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to transfer credits.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $user = User::findOrFail($id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User Credits';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['create_user'] = auth()->user()->can('create-user');
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id, 'credits' => auth()->user()->credits],
            'permission' => $permission,
            'user_profile' => $user
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
        
        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user-profile', $id) || Gate::denies('transfer-credits', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        if ($request->top_up) {
            $this->validate($request, [
                'input_credits' => 'bail|required|integer|min:1|max:1',
            ]);
        } else {
            if(auth()->user()->isAdmin()) {
                $this->validate($request, [
                    'input_credits' => 'bail|required|integer|between:-20,' . $db_settings->settings['max_transfer_credits'],
                ]);
            } else {
                $this->validate($request, [
                    'input_credits' => 'bail|required|integer|between:1,' . $db_settings->settings['max_transfer_credits'],
                ]);
            }

        }

        if (!auth()->user()->isAdmin() && auth()->user()->credits < $request->input_credits) {
            return response()->json([
                'message' => 'Input must be lower or equal to your available credits.',
            ], 403);
        }

        $user = User::findOrFail($id);

        if ($request->top_up) {
            $current = Carbon::now();
            $dt = Carbon::parse($user->getOriginal('expired_at'));
            if($current->lt($dt)) {
                if($user->vpn_session == 3) {
                    $user->expired_at = $dt->addSeconds(2595600 / 2);
                } else if($user->vpn_session == 4) {
                    $user->expired_at = $dt->addSeconds(2595600 / 3);
                } else {
                    $user->expired_at = $dt->addSeconds(2595600);
                }
            } else {
                if($user->vpn_session == 3) {
                    $user->expired_at = $current->addSeconds(2595600 / 2);
                } else if($user->vpn_session == 4) {
                    $user->expired_at = $current->addSeconds(2595600 / 3);
                } else {
                    $user->expired_at = $current->addSeconds(2595600);
                }
            }
        } else {
            if ($request->input_credits < 0 && ($user->credits + $request->input_credits) < 0) {
                return response()->json(['message' => 'User credits must be a non-negative value.'], 403);
            }

            $user->credits += $request->input_credits;
        }

        $user->save();

        if (!auth()->user()->isAdmin()) {
            $request->user()->credits -= $request->input_credits;
            $request->user()->save();
        }

        if ($request->top_up) {
            $message = 'User has been successfully top-up.';
        } else {
            $message = 'Credits has been transferred successfully.';
        }

        return response()->json([
            'message' => $message,
            'profile' => auth()->user(),
            'user_profile' => $user
        ], 200);
    }
}
