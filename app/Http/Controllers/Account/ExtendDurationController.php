<?php

namespace App\Http\Controllers\Account;

use App\Lang;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExtendDurationController extends Controller
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

    public function index()
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
        $permission['manage_update_json'] = auth()->user()->can('manage-update-json');

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Extend Duration';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $language = Lang::all()->pluck('name');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'credits' => auth()->user()->credits, 'expired_at' => auth()->user()->expired_at, 'distributor' => auth()->user()->distributor],
            'language' => $language,
            'permission' => $permission
        ], 200);
    }

    public function extend(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Admin account cannot extend duration.',
            ], 403);
        }

        $this->validate($request, [
            'credits' => 'bail|required|integer|between:1,3',
        ]);

        if (!auth()->user()->isAdmin() && auth()->user()->credits < $request->credits) {
            return response()->json([
                'message' => 'Input must be lower or equal to your available credits.',
            ], 403);
        }

        DB::transaction(function () use ($request) {

            $account = User::findorfail(auth()->user()->id);

            $current = Carbon::now();
            $expired_at = Carbon::parse($account->getOriginal('expired_at'));

            if($current->lt($expired_at)) {
                $new_expired_at = $expired_at->addSeconds((2595600 * $request->credits) / intval($account->user_package->user_package['cost']));
            } else {
                $new_expired_at = $current->addSeconds((2595600 * $request->credits) / intval($account->user_package->user_package['cost']));
            }

            DB::table('users')->where('id', $account->id)->update(['expired_at' => $new_expired_at, 'credits' => $account->can('unlimited-credits') ? $account->getOriginal('credits') : $account->getOriginal('credits') - $request->credits]);

            $date_now = Carbon::now();
            DB::table('user_credit_logs')->insert([
                [
                    'user_id' => $account->id,
                    'user_id_related' => $account->id,
                    'type' => 'TOP-UP',
                    'direction' => 'OUT',
                    'credit_used' => $request->credits,
                    'duration' => Carbon::now()->addSeconds((2595600 * $request->credits) / intval($account->user_package->user_package['cost']))->diffInDays() . ' Days',
                    'credit_before' => $account->credits,
                    'credit_after' => $account->credits == 'No Limit' ? $account->credits : $account->getOriginal('credits') - $request->credits,
                    'created_at' => $date_now,
                    'updated_at' => $date_now,
                ],
                [
                    'user_id' => $account->id,
                    'user_id_related' => $account->id,
                    'type' => 'TOP-UP',
                    'direction' => 'IN',
                    'credit_used' => $request->credits,
                    'duration' => Carbon::now()->addSeconds((2595600 * $request->credits) / intval($account->user_package->user_package['cost']))->diffInDays() . ' Days',
                    'credit_before' => $account->credits,
                    'credit_after' => $account->credits == 'No Limit' ? $account->credits : $account->getOriginal('credits') - $request->credits,
                    'created_at' => $date_now,
                    'updated_at' => $date_now,
                ]
            ]);

            DB::table('admin_transfer_logs')->insert([
                [
                    'user_id_from' => $account->id,
                    'user_id_to' => $account->id,
                    'type' => 'TOP-UP',
                    'credit_used' => $request->credits,
                    'credit_before_from' => $account->credits,
                    'credit_after_from' => $account->credits == 'No Limit' ? $account->credits : $account->credits - $request->credits,
                    'credit_before_to' => $account->credits,
                    'credit_after_to' => $account->credits == 'No Limit' ? $account->credits : $account->credits - $request->credits,
                    'duration_before' => Carbon::parse($account->getOriginal('expired_at')),
                    'duration_after' => $new_expired_at,
                    'duration' => Carbon::now()->addSeconds((2595600 * $request->credits) / intval($account->user_package->user_package['cost']))->diffInDays() . ' Days',
                    'created_at' => $date_now,
                    'updated_at' => $date_now,
                ],
            ]);

        }, 5);

        $account = User::findorfail(auth()->user()->id);
        $withs = $request->credits > 1 ? ' credits' : ' credit';

        return response()->json([
            'message' => 'You have extend your duration using ' . $request->credits . ' ' . $withs . '.',
            'profile' => ['credits' => $account->credits, 'expired_at' => $account->expired_at],
        ], 200);

    }
}
