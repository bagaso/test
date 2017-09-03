<?php

namespace App\Http\Controllers\ManageUser;

use App\User;
use App\VoucherCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserVoucherController extends Controller
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
            'user_profile' => ['username' => $user->username, 'expired_at' => $user->expired_at],
        ], 200);
    }

    public function applyVoucher(Request $request, $id)
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
            'voucher_code' => 'bail|required|exists:voucher_codes,code',
        ]);

        $voucher = VoucherCode::where('code', $request->voucher_code)->first();

        if(!is_null($voucher->user_id)) {
            return response()->json([
                'voucher_code' => ['Voucher code is already used.'],
            ], 422);
        }

        $user = User::with('user_package')->findorfail($id);

        $current = Carbon::now();
        $expired_at = Carbon::parse($user->getOriginal('expired_at'));

        if($current->lt($expired_at)) {
            $user->expired_at = $expired_at->addSeconds($voucher->getOriginal('duration') / intval($user->user_package->user_package['cost']));
        } else {
            $user->expired_at = $current->addSeconds($voucher->getOriginal('duration') / intval($user->user_package->user_package['cost']));
        }

        $user->save();
        $voucher->user_id = $user->id;
        $voucher->save();

        return response()->json([
            'message' => 'Voucher code applied to user.',
            'user_profile' => ['expired_at' => $user->expired_at],
        ], 200);
    }
}
