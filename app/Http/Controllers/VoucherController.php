<?php

namespace App\Http\Controllers;

use App\Lang;
use App\VoucherCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VoucherController extends Controller
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

    public function generateVoucherIndex(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['manage_vpn_server'] = auth()->user()->can('manage-vpn-server');
        $permission['manage_voucher'] = auth()->user()->can('manage-voucher');

        $language = Lang::all()->pluck('name');

        if (Gate::denies('manage-voucher')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $data = VoucherCode::SearchPaginateAndOrder($request);

        $columns = [
            'code', 'duration', 'updated_at', 'created_at',
        ];

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Generate Voucher';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'user_group_id' => auth()->user()->user_group_id, 'credits' => auth()->user()->credits],
            'language' => $language,
            'permission' => $permission,
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function generate(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (Gate::denies('manage-voucher')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'credits' => 'bail|required|integer|between:1,5',
        ]);
        
        if(auth()->user()->cannot('unlimited-credits')) {
            if(auth()->user()->credits < $request->credits) {
                return response()->json([
                    'message' => 'Input must be lower or equal to your available credits.',
                ], 404);
            }
        }
        
        $voucher = array();
        DB::beginTransaction();
        try {
            $vouchers = array();
            for($i=0;$i<=$request->credits-1;$i++) {
                $temp = Carbon::now()->format('y') . strtoupper(str_random(5)) . '-' . Carbon::now()->format('m') . strtoupper(str_random(6)) . '-' . Carbon::now()->format('d') . strtoupper(str_random(7));
                $voucher[] = $temp;
                $vouchers[$i]['code'] = $temp;
                $vouchers[$i]['created_user_id'] = auth()->user()->id;
                $vouchers[$i]['duration'] = 2592000 + 3600;
                if(auth()->user()->isAdmin()) {
                    $vouchers[$i]['duration'] = 2592000 + 3600;
                }
                $vouchers[$i]['created_at'] = Carbon::now();
                $vouchers[$i]['updated_at'] = Carbon::now();;
            }
            VoucherCode::insert($vouchers);
            if(auth()->user()->cannot('unlimited-credits')) {
                $account = $request->user();
                $account->credits -= $request->credits;
                $account->save();
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json([
                'message' => $ex->getMessage(),
            ], 403);
        }

        $data = VoucherCode::SearchPaginateAndOrder($request);

        $columns = [
            'code', 'duration', 'updated_at', 'created_at',
        ];

        return response()->json([
            'message' => 'Voucher generated.',
            'profile' => ['user_group_id' => auth()->user()->user_group_id, 'credits' => auth()->user()->credits],
            'voucher' => $voucher,
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }
    
    public function applyVoucherIndex()
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        return response()->json([
            'profile' => ['expired_at' => auth()->user()->expired_at],
        ], 200);
    }
    
    public function applyVoucher(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Admin account cannot use voucher.',
            ], 403);
        }

        $this->validate($request, [
            'voucher_code' => 'bail|required',
        ]);

        $voucher = VoucherCode::where('code', $request->voucher_code)->first();

        if(count($voucher) == 0) {
            return response()->json([
                'voucher_code' => ['Voucher code is invalid.'],
            ], 422);
        }

        if(!is_null($voucher->user_id)) {
            return response()->json([
                'voucher_code' => ['Voucher code is already used.'],
            ], 422);
        }

        $account = $request->user();

        $current = Carbon::now();
        $expired_at = Carbon::parse($account->getOriginal('expired_at'));

        if($current->lt($expired_at)) {
            if($account->vpn_session == 3) {
                $account->expired_at = $expired_at->addSeconds(2595600 / 2);
            } else if($account->vpn_session == 4) {
                $account->expired_at = $expired_at->addSeconds(2595600 / 3);
            } else {
                $account->expired_at = $expired_at->addSeconds(2595600);
            }
        } else {
            if($account->vpn_session == 3) {
                $account->expired_at = $current->addSeconds(2595600 / 2);
            } else if($account->vpn_session == 4) {
                $account->expired_at = $current->addSeconds(2595600 / 3);
            } else {
                $account->expired_at = $current->addSeconds(2595600);
            }
        }

        $account->save();
        $voucher->user_id = auth()->user()->id;
        $voucher->save();

        return response()->json([
            'message' => 'Voucher code applied.',
            'profile' => ['expired_at' => auth()->user()->expired_at],
        ], 200);
    }

    public function deleteVoucher(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->cannot('manage-voucher')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }
        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        $vouchers = VoucherCode::whereIn('id', $request->id);
        $vouchers->delete();

        $data = VoucherCode::SearchPaginateAndOrder($request);

        $columns = [
            'code', 'duration', 'updated_at', 'created_at',
        ];

        return response()->json([
            'message' => 'Voucher deleted.',
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }
}
