<?php

namespace App\Http\Controllers\Account;

use App\Lang;
use App\UserCreditLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserCreditLogController extends Controller
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
        $site_options['sub_name'] = 'Credit Transfer Logs';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $language = Lang::all()->pluck('name');

        $account_transfer_logs = UserCreditLog::with(['user_related'])->where('user_id', auth()->user()->id)->orderBy('id', 'desc')->skip(0)->take(100)->get();

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username, 'credits' => auth()->user()->credits],
            'model' => $account_transfer_logs,
            'language' => $language,
            'permission' => $permission
        ], 200);
    }
}
