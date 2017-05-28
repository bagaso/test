<?php

namespace App\Http\Controllers\Admin;

use App\Lang;
use App\VpnHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VpnHistoryLogController extends Controller
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
        $site_options['sub_name'] = 'VPN History Logs';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        if($request->has('username') || $request->has('user_ip') || $request->has('user_port')) {
            $vpn_logs = VpnHistory::query();
            $vpn_logs = $vpn_logs->whereHas('user_related', function($q) use ($request) {
                if($request->has('username')) {
                    $q->where('username', 'LIKE', '%'. $request->username .'%');
                }
            });
            if($request->has('user_ip')) {
                $vpn_logs = $vpn_logs->where('user_ip', 'LIKE', '%'. $request->user_ip .'%');
            }
            if($request->has('user_port')) {
                $vpn_logs = $vpn_logs->where('user_port', 'LIKE', '%'. $request->user_port .'%');
            }
            if($request->has('date_from') && $request->has('date_to')) {
                $vpn_logs = $vpn_logs->where('session_start', '>=', Carbon::parse($request->date_from)->timezone('Asia/Manila'))
                    ->where('session_end', '<=', Carbon::parse($request->date_to)->timezone('Asia/Manila'));
            }
            $vpn_logs = $vpn_logs->orderBy('id', 'desc')->skip(0)->take(500)->get();
        } else {
            $vpn_logs = VpnHistory::query();
            if($request->has('date_from') && $request->has('date_to')) {
                $vpn_logs = $vpn_logs->where('session_start', '>=', Carbon::parse($request->date_from)->timezone('Asia/Manila'))
                    ->where('session_end', '<=', Carbon::parse($request->date_to)->timezone('Asia/Manila'));
            }
            $vpn_logs = $vpn_logs->orderBy('id', 'desc')->skip(0)->take(500)->get();
        }

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'model' => $vpn_logs,
            'language' => $language,
            'permission' => $permission
        ], 200);
    }
}
