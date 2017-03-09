<?php

namespace App\Http\Controllers\NewsAndUpdates;

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
        $permission['is_admin'] = auth()->user()->isAdmin();
        $permission['update_account'] = auth()->user()->can('update-account');
        $permission['manage_user'] = auth()->user()->can('manage-user');

        $post = Updates::findorfail($request->id);

        return response()->json([
            'profile' => auth()->user(),
            'permission' => $permission,
            'post' => $post,
        ], 200);
    }
}
