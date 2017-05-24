<?php

namespace App\Http\Controllers\Admin;

use App\AdminTransferLog;
use App\Lang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CreditTransferLogController extends Controller
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

    public function index(Request $request)
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

        $language = Lang::all()->pluck('name');

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Credit Transfer Logs';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        if($request->has('user_from') || $request->has('user_to')) {
            $transfer_logs = AdminTransferLog::wherehas('userfrom1', function($q) use ($request) {
                if($request->has('user_from')) {
                    $q->where('username', 'LIKE', '%'. $request->user_from .'%');
                }
            })->wherehas('userto1', function($q) use ($request) {
                if($request->has('user_to')) {
                    $q->where('username', 'LIKE', '%'. $request->user_to .'%');
                }
            })->orderBy('created_at', 'desc')->skip(0)->take(100)->get();
        } else {
            $transfer_logs = AdminTransferLog::orderBy('created_at', 'desc')->skip(0)->take(100)->get();
        }

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'model' => $transfer_logs,
            'language' => $language,
            'permission' => $permission
        ], 200);
    }
}
