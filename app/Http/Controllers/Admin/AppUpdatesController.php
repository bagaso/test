<?php

namespace App\Http\Controllers\Admin;

use App\JsonUpdate;
use App\Lang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class AppUpdatesController extends Controller
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

    public function app_android_index()
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

        if (Gate::denies('manage-update-json')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Android Updates';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        
        $json = JsonUpdate::findorfail(1);

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'json' => $json->json,
            'language' => $language,
            'permission' => $permission
        ], 200);
    }

    public function app_android_update(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (Gate::denies('manage-update-json')) {
            return response()->json([
                'message' => 'Action not allowed.'
            ], 403);
        }

        $this->validate($request, [
            'json' => 'bail|required|json',
        ]);

        $json = JsonUpdate::findorfail(1);
        $json->json = $request->json;
        $json->save();

        return response()->json([
            'message' => 'Json file updated.',
        ], 200);
    }

    public function app_gui_index()
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

        if (Gate::denies('manage-update-json')) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'language' => $language,
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'GUI Updates';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $json = JsonUpdate::findorfail(2);

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'json' => $json->json,
            'language' => $language,
            'permission' => $permission
        ], 200);
    }

    public function app_gui_update(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        if (auth()->user()->cannot('update-account') || !auth()->user()->isAdmin() && !$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'message' => 'Logged out.',
            ], 401);
        }

        if (Gate::denies('manage-update-json')) {
            return response()->json([
                'message' => 'Action not allowed.'
            ], 403);
        }

        $this->validate($request, [
            'json' => 'bail|required|json',
        ]);

        $json = JsonUpdate::findorfail(2);
        $json->json = $request->json;
        $json->save();

        return response()->json([
            'message' => 'Json file updated.',
        ], 200);
    }
}
