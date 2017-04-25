<?php

namespace App\Http\Controllers\Account;

use App\Lang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

        if (!auth()->user()->isAdmin()) {
            $current = Carbon::now();
            $expired_at = Carbon::parse($request->user()->getOriginal('expired_at'));
            if($current->lt($expired_at)) {
                $request->user()->expired_at = $expired_at->addSeconds((2595600 * $request->credits) / intval($request->user()->user_package->user_package['cost']));
            } else {
                $request->user()->expired_at = $current->addSeconds((2595600 * $request->credits) / intval($request->user()->user_package->user_package['cost']));
            }
            if(auth()->user()->cannot('unlimited-credits')) {
                $request->user()->credits -= $request->credits;
            }
            $request->user()->save();
        }

        $withs = $request->credits > 1 ? ' credits' : ' credit';
        return response()->json([
            'message' => 'You have extend your duration using ' . $request->credits . ' ' . $withs . '.',
            'profile' => ['credits' => auth()->user()->credits, 'expired_at' => auth()->user()->expired_at],
        ], 200);

    }
}
