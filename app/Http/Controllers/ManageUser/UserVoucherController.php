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

    public function index(Request $request, $id)
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
        if (Gate::denies('apply-voucher', $id)) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to apply voucher to the user.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        if(auth()->user()->isAdmin()) {
            $data = VoucherCode::where('user_id', $id)->SearchPaginateAndOrder($request);
        } else {
            $data = VoucherCode::where([['user_id', $id],['created_user_id', auth()->user()->id]])->SearchPaginateAndOrder($request);
        }

        $columns = [
            'code', 'duration', 'updated_at', 'created_at',
        ];

        $user = User::findOrFail($id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'User Voucher';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $permission['create_user'] = auth()->user()->can('create-user');
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id],
            'permission' => $permission,
            'user_profile' => $user,
            'model' => $data,
            'columns' => $columns
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

        if (auth()->user()->id == $id || Gate::denies('manage-user') || Gate::denies('update-user-profile', $id) || Gate::denies('apply-voucher', $id)) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'voucher_code' => 'bail|required',
        ]);

        $voucher = VoucherCode::where('code', $request->voucher_code)->first();

        if(count($voucher) == 0) {
            return response()->json([
                'message' => 'Voucher code is invalid.',
            ], 403);
        }

        if(!is_null($voucher->user_id)) {
            return response()->json([
                'message' => 'Voucher code is already used.',
            ], 403);
        }

        $user = User::findorfail($id);

        $current = Carbon::now();
        $expired_at = Carbon::parse($user->getOriginal('expired_at'));

        if($current->lt($expired_at)) {
            if($user->vpn_session == 3) {
                $user->expired_at = $expired_at->addSeconds($voucher->getOriginal('duration') / 2);
            } else if($user->vpn_session == 4) {
                $user->expired_at = $expired_at->addSeconds($voucher->getOriginal('duration') / 3);
            } else {
                $user->expired_at = $expired_at->addSeconds($voucher->getOriginal('duration'));
            }
        } else {
            if($user->vpn_session == 3) {
                $user->expired_at = $current->addSeconds($voucher->getOriginal('duration') / 2);
            } else if($user->vpn_session == 4) {
                $user->expired_at = $current->addSeconds($voucher->getOriginal('duration') / 3);
            } else {
                $user->expired_at = $current->addSeconds($voucher->getOriginal('duration'));
            }
        }

        $user->save();
        $voucher->user_id = $user->id;
        $voucher->save();

        return response()->json([
            'message' => 'Voucher code applied to user.',
        ], 200);
    }
}
