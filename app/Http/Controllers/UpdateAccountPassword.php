<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UpdateAccountPassword extends Controller
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

        if($request->password != '' && !Hash::check($request->password, $account->getAuthPassword())) {
            return response()->json(['password' => ['Your old password is incorrect.']], 422);
        }

        $this->validate($request, [
            'password' => 'required',
            'new_password' => 'bail|required|between:6,15|confirmed',
            'new_password_confirmation' => 'bail|required|between:6,15',
        ]);

        $account->password = $request->new_password;
        $account->save();

        return response()->json(['message' => 'Password changed successfully.'], 200);

    } // function update

} // end class
