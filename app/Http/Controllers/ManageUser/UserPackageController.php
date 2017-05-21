<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use App\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserPackageController extends Controller
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
        $userpackage = UserPackage::where('is_active', 1)->get();

        return response()->json([
            'user_profile' => ['username' => $user->username, 'user_package_id' => $user->user_package_id],
            'user_package_list' => $userpackage,
        ], 200);
    }

    public function user_package(Request $request, $id)
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
            'user_package_id' => 'bail|required|integer|in:1,2,3,4',
        ]);

        $user = User::findorfail($id);

        $current_copy = Carbon::now();
        $expired_at = Carbon::parse($user->getOriginal('expired_at'));
        
        $user_package = UserPackage::findorfail($request->user_package_id);

        if(auth()->user()->cannot('force-package-upgrade')) {
            // $days_left = $current_copy->diffInSeconds($expired_at);
            $days_left = $current_copy->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost']);
            $dt = $current_copy->addSeconds($days_left);
            if($user_package->id > 1 && $dt->diffInDays() <= intval($user_package->user_package['minimum_duration'])) {
                return response()->json([
                    'message' => 'User must have ' . $user_package->user_package['minimum_duration'] . ' day(s) of remaining duration.',
                ], 403);
            }
        }

        $current = Carbon::now();

        if($user->user_package_id <> $user_package->id && $current->lt($expired_at)) {
            $add = 0;
            if($user->user_package->id == 1) {
                $add = $current->diffInSeconds($expired_at) / intval($user_package->user_package['cost']);
            }
            if($user->user_package->id == 2) {
                if($user_package->id == 1) {
                    $add = $current->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost']);
                }
                if($user_package->id == 3) {
                    $add = ($current->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost'])) / intval($user_package->user_package['cost']);
                }
            }
            if($user->user_package->id == 3) {
                if($user_package->id == 1) {
                    $add = $current->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost']);
                }
                if($user_package->id == 2) {
                    $add = ($current->diffInSeconds($expired_at) * intval($user->user_package->user_package['cost'])) / intval($user_package->user_package['cost']);
                }
            }
            $user->expired_at = $current->addSeconds($add);
        }
        $user->user_package_id = $user_package->id;
        $user->save();

        $user = User::with('user_package')->findorfail($user->id);

        return response()->json([
            'message' => 'User package updated.',
            'user_profile' => ['user_package' => $user->user_package, 'expired_at' => $user->expired_at]
        ], 200);
    }
}
