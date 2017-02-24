<?php

namespace App\Http\Controllers;

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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if(auth()->user()->isAdmin()) {
            $data = VoucherCode::SearchPaginateAndOrder($request);
        } else {
            $data = VoucherCode::where('created_user_id', auth()->user()->id)->SearchPaginateAndOrder($request);
        }

        $columns = [
            'code', 'duration', 'updated_at', 'created_at',
        ];

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
            'columns' => $columns
        ], 200);
    }

    public function generate(Request $request)
    {
        if(auth()->user()->isAdmin()) {
            $this->validate($request, [
                'credits' => 'bail|required|integer|between:1,5',
            ]);
        } else {
            $this->validate($request, [
                'credits' => 'bail|required|integer|between:1,5',
            ]);
        }
        
        if(!auth()->user()->isAdmin()) {
            if(auth()->user()->credits < $request->credits) {
                return response()->json([
                    'message' => 'Input must be lower or equal to your available credits.',
                ], 404);
            }
        }
        
        $voucher = array();
        DB::beginTransaction();
        try {
            $time_now = Carbon::now();
            $vouchers = array();
            for($i=0;$i<=$request->credits-1;$i++) {
                $temp = Carbon::now()->format('y') . strtoupper(str_random(5)) . '-' . Carbon::now()->format('m') . strtoupper(str_random(6)) . '-' . Carbon::now()->format('d') . strtoupper(str_random(7));
                $voucher[] = $temp;
                $vouchers[$i]['code'] = $temp;
                $vouchers[$i]['created_user_id'] = auth()->user()->id;
                $vouchers[$i]['duration'] = 0;
                $vouchers[$i]['duration_months'] = 0;
                $vouchers[$i]['duration_days'] = 30;
                $vouchers[$i]['duration_hours'] = 0;
                $vouchers[$i]['created_at'] = $time_now;
                $vouchers[$i]['updated_at'] = $time_now;
            }
            VoucherCode::insert($vouchers);
            if(!auth()->user()->isAdmin()) {
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

        return response()->json([
            'message' => 'Voucher generated.',
            'profile' => auth()->user(),
            'voucher' => $voucher,
        ], 200);
    }
    
    public function applyVoucherIndex(Request $request)
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (Gate::denies('update-account')) {
            return response()->json([
                'message' => 'Action not allowed.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }
        
        $data = VoucherCode::where('user_id', auth()->user()->id)->SearchPaginateAndOrder($request);

        $columns = [
            'code', 'duration', 'updated_at',
        ];

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'model' => $data,
            'columns' => $columns
        ], 200);
    }
    
    public function applyVoucher(Request $request)
    {
        if (auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Admin account cannot use vouchers.',
            ], 403);
        }
        
        if (Gate::denies('update-account')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'voucher_code' => 'bail|required',
        ]);

        $voucher = VoucherCode::where('code', $request->voucher_code)->firstorfail();

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

        $account = $request->user();

        $current = Carbon::now();
        $expired_at = Carbon::parse($account->getOriginal('expired_at'));

        if($current->lt($expired_at)) {
            $account->expired_at = $expired_at->addMonths($voucher->duration_months)->addDays($voucher->duration_days)->addHours($voucher->duration_hours);
        } else {
            $account->expired_at = $current->addMonths($voucher->duration_months)->addDays($voucher->duration_days)->addHours($voucher->duration_hours);
        }

        $account->save();
        $voucher->user_id = auth()->user()->id;
        $voucher->save();

        return response()->json([
            'message' => 'Voucher code applied.',
        ], 200);
    }
}
