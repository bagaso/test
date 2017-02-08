<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class UpdateAccount extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth:api']);
        
    } // function __construct

    public function update(Request $request)
    {
        // $this->authorize('update-account');

        if (Gate::denies('update-account')) {
            return response()->json(['message' => 'Action not allowed.'], 403);
        }

        $account = $request->user();

        $this->validate($request, [
            'username' => 'sometimes|bail|required|alpha_num|between:6,20|unique:users,username,' . $account->id,
            'email' => 'bail|required|email|max:50|unique:users,email,' . $account->id,
            'fullname' => 'bail|required|max:50',
        ]);

        if (auth()->user()->isAdmin()) {
            $account->username = $request->username;
        }
        $account->email = $request->email;
        $account->fullname = $request->fullname;
        $account->save();
        
        return response()->json([
            'message' => 'Profile updated successfully.',
            'account' => ['updated_at' => $account->updated_at]
        ], 200);
        
    } // function update

} // end class
