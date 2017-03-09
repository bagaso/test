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
        $permission['generate_voucher'] = auth()->user()->can('generate-voucher');

        if (Gate::denies('manage-user') || Gate::denies('generate-voucher')) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

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
            'columns' => $columns,
        ], 200);
    }

    public function generate(Request $request)
    {
        if (Gate::denies('manage-user') || Gate::denies('generate-voucher')) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }
        
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

        if(auth()->user()->isAdmin()) {
            $data = VoucherCode::SearchPaginateAndOrder($request);
        } else {
            $data = VoucherCode::where('created_user_id', auth()->user()->id)->SearchPaginateAndOrder($request);
        }

        $columns = [
            'code', 'duration', 'updated_at', 'created_at',
        ];

        return response()->json([
            'message' => 'Voucher generated.',
            'profile' => auth()->user(),
            'voucher' => $voucher,
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }
    
    public function applyVoucherIndex(Request $request)
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');
        $permission['generate_voucher'] = auth()->user()->can('generate-voucher');

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
            'columns' => $columns,
        ], 200);
    }
    
    public function applyVoucher(Request $request)
    {
        if (auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Admin account cannot use voucher.',
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
            $account->expired_at = $expired_at->addSeconds($voucher->getOriginal('duration'));
        } else {
            $account->expired_at = $current->addSeconds($voucher->getOriginal('duration'));
        }

        $account->save();
        $voucher->user_id = auth()->user()->id;
        $voucher->save();

        $data = VoucherCode::where('user_id', auth()->user()->id)->SearchPaginateAndOrder($request);

        $columns = [
            'code', 'duration', 'updated_at',
        ];

        return response()->json([
            'message' => 'Voucher code applied.',
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function deleteVoucher(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }
        $this->validate($request, [
            'id' => 'bail|required|array',
        ]);

        $vouchers = VoucherCode::whereIn('id', $request->id);
        $vouchers->delete();

        if(auth()->user()->isAdmin()) {
            $data = VoucherCode::SearchPaginateAndOrder($request);
        } else {
            $data = VoucherCode::where('created_user_id', auth()->user()->id)->SearchPaginateAndOrder($request);
        }

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
