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
        
        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
        ], 200);
    }
    
    public function create(Request $request)
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

        $new_post = new Updates;
        $new_post->create($request->all());
        return response()->json([
            'message' => 'New post created.'
        ], 200); 
    }
}
