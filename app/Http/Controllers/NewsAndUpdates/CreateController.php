<?php

namespace App\Http\Controllers\NewsAndUpdates;

use App\Updates;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CreateController extends Controller
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

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'No permission to access this page.',
                'profile' => ['username' => auth()->user()->username],
                'permission' => $permission,
            ], 403);
        }

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Create Post';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        
        return response()->json([
            'site_options' => $site_options,
            'profile' => ['username' => auth()->user()->username],
            'permission' => $permission,
        ], 200);
    }
    
    public function create(Request $request)
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
            'title' => 'required',
            'content' => 'required',
        ]);

        $new_post = new Updates;
        $new_post->title = $request->title;
        $new_post->content = $request->content;
        $new_post->pinned = $request->pinned;
        $new_post->is_public = $request->is_public;
        $new_post->save();
        return response()->json([
            'message' => 'New post created.'
        ], 200); 
    }
}
