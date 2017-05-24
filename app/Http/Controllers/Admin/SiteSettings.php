<?php

namespace App\Http\Controllers\Admin;

use App\Lang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SiteSettings extends Controller
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

        $site_settings = [
            'site_name' => $db_settings->settings['site_name'],
            'backup' => $db_settings->settings['backup'],
            'db_backup_schedule' => $db_settings->settings['db_cron'],
            'data_reset' => $db_settings->settings['data_reset'],
            'data_reset_schedule' => $db_settings->settings['data_reset_cron'],
            'data_allowance' => $db_settings->settings['consumable_data'],
            'trial_period' => $db_settings->settings['trial_period'],
            'max_transfer_credits' => $db_settings->settings['max_transfer_credits'],
            'enable_panel_login' => $db_settings->settings['enable_panel_login'],
            'enable_vpn_login' => $db_settings->settings['enable_vpn_login'],
            'public_credit_distributors' => $db_settings->settings['public_credit_distributors'],
            'public_online_users' => $db_settings->settings['public_online_users'],
            'public_server_status' => $db_settings->settings['public_server_status'],
        ];

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Settings';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'permission' => $permission,
            'site_settings' => $site_settings,
        ], 200);
    }

    public function updateSettings(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'site_name' => 'required',
            'backup' => 'required|boolean',
            'data_reset' => 'required|boolean',
            'db_backup_schedule' => 'required_if:backup,true',
            'data_reset_schedule' => 'required_if:data_reset,true',
            'data_allowance' => 'required|integer',
            'trial_period' => 'required|integer',
            'max_transfer_credits' => 'required|integer|min:1',
            'enable_panel_login' => 'required|boolean',
            'enable_vpn_login' => 'required|boolean',
            'public_credit_distributors' => 'required|boolean',
            'public_online_users' => 'required|boolean',
            'public_server_status' => 'required|boolean',
        ]);

        if($request->backup && !\Cron\CronExpression::isValidExpression($request->db_backup_schedule)) {
            return response()->json([
                'db_backup_schedule' => ['DB backup schedule input must be a valid cron format'],
            ], 422);
        }

        if($request->data_reset && !\Cron\CronExpression::isValidExpression($request->data_reset_schedule)) {
            return response()->json([
                'data_reset_schedule' => ['Data reset schedule input must be a valid cron format'],
            ], 422);
        }

        $db_settings->timestamps = false;
        $site_settings = $db_settings->settings;

        $site_settings['site_name'] = $request->site_name;
        $site_settings['backup'] = $request->backup;
        $site_settings['data_reset'] = $request->data_reset;
        $site_settings['db_cron'] = $request->backup ? $request->db_backup_schedule : $site_settings['db_cron'];
        $site_settings['data_reset_cron'] = $request->data_reset ? $request->data_reset_schedule : $site_settings['data_reset_cron'];
        $site_settings['consumable_data'] = $request->data_allowance;
        $site_settings['trial_period'] = $request->trial_period;
        $site_settings['max_transfer_credits'] = $request->max_transfer_credits;
        $site_settings['enable_panel_login'] = $request->enable_panel_login;
        $site_settings['enable_vpn_login'] = $request->enable_vpn_login;
        $site_settings['public_credit_distributors'] = $request->public_credit_distributors;
        $site_settings['public_online_users'] = $request->public_online_users;
        $site_settings['public_server_status'] = $request->public_server_status;

        $db_settings->settings = $site_settings;

        $db_settings->save();

        return response()->json([
            'message' => 'Settings updated.',
        ], 200);
    }
}
