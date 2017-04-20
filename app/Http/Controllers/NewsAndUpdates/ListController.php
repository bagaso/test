<?php

namespace App\Http\Controllers\NewsAndUpdates;

use App\Lang;
use App\Updates;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ListController extends Controller
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

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'News and Updates';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];

        $language = Lang::all();

        $data = Updates::orderBy('pinned', 'desc')->orderBy('id', 'desc')->paginate(10);

        $columns = [
            'title', 'pinned', 'created_at',
        ];

        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'language' => $language,
            'permission' => $permission,
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }
    
    public function deletePost(Request $request)
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
            'id' => 'bail|required|array',
        ]);

        $posts = Updates::whereIn('id', $request->id);
        $posts->delete();

        $data = Updates::orderBy('pinned', 'desc')->orderBy('id', 'desc')->paginate(10);

        $columns = [
            'title', 'pinned', 'created_at',
        ];

        return response()->json([
            'message' => 'Selected posts deleted',
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function pinPost(Request $request)
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
            'id' => 'bail|required|array',
        ]);

        $posts = Updates::whereIn('id', $request->id);
        $posts->update(['pinned' => 1]);

        $data = Updates::orderBy('pinned', 'desc')->orderBy('id', 'desc')->paginate(10);

        $columns = [
            'title', 'pinned', 'created_at',
        ];

        return response()->json([
            'message' => 'Selected posts pinned',
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }

    public function unPinPost(Request $request)
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
            'id' => 'bail|required|array',
        ]);

        $posts = Updates::whereIn('id', $request->id);
        $posts->update(['pinned' => 0]);

        $data = Updates::orderBy('pinned', 'desc')->orderBy('id', 'desc')->paginate(10);

        $columns = [
            'title', 'pinned', 'created_at',
        ];

        return response()->json([
            'message' => 'Selected posts unpinned',
            'model' => $data,
            'columns' => $columns,
        ], 200);
    }
}
