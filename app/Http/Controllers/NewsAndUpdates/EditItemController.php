<?php

namespace App\Http\Controllers\NewsAndUpdates;

use App\Updates;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EditItemController extends Controller
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

    public function index($id)
    {
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'No permission to access this page.',
                'profile' => auth()->user(),
                'permission' => $permission,
            ], 403);
        }

        $post = Updates::findorfail($id);

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'post' => $post,
        ], 200);
    }
    
    public function update(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Action not allowed.',
            ], 403);
        }

        $this->validate($request, [
            'title' => 'required',
            'content' => 'required',
        ]);
        
        $post = Updates::findorfail($id);

        $post->title = $request->title;
        $post->content = $request->content;
        $post->pinned = $request->pinned;
        $post->save();

        return response()->json([
            'message' => 'Post updated.'
        ], 200);
    }
}
