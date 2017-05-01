<?php

namespace App\Http\Controllers\NewsAndUpdates;

use App\Lang;
use App\Updates;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PublicNewsAndUpdatesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index()
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'News and Updates';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $site_options['public_online_users'] = $db_settings->settings['public_online_users'] && $site_options['enable_panel_login'];
        $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'] && $site_options['enable_panel_login'];
        $site_options['public_server_status'] = $db_settings->settings['public_server_status'] && $site_options['enable_panel_login'];

        $language = Lang::all()->pluck('name');

        $data = Updates::where('is_public', 1)->orderBy('pinned', 'desc')->orderBy('id', 'desc')->paginate(10);

        $columns = [
            'title', 'pinned', 'created_at',
        ];

        return response()->json([
            'site_options' => $site_options,
            'language' => $language,
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function item(Request $request)
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        $language = Lang::all()->pluck('name');
        $post = Updates::findorfail($request->id);

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = $post->title;
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $site_options['public_online_users'] = $db_settings->settings['public_online_users'] && $site_options['enable_panel_login'];
        $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'] && $site_options['enable_panel_login'];
        $site_options['public_server_status'] = $db_settings->settings['public_server_status'] && $site_options['enable_panel_login'];

        if (!$post->is_public) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error', 'public_online_users' => $site_options['public_online_users'], 'public_credit_distributors' => $db_settings->settings['public_credit_distributors']],
                'message' => 'Please Login to access this page.',
            ], 403);
        }

        return response()->json([
            'site_options' => $site_options,
            'language' => $language,
            'post' => $post,
        ], 200);
    }
}
