<?php

namespace App\Http\Controllers\NewsAndUpdates;

use App\Lang;
use App\Updates;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ItemController extends Controller
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

        $post = Updates::findorfail($request->id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = $post->title;
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $language = Lang::all()->pluck('name');

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'permission' => $permission,
            'post' => $post,
        ], 200);
    }
}
