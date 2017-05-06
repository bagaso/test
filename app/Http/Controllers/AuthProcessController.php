<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class AuthProcessController extends Controller
{
    use AuthenticatesUsers;

    protected $maxLoginAttempts=5;
    protected $lockoutTime=300;

    public function login(Request $request)
    {
//        $credentials = $request->only('email', 'password');
//
//        $this->validate($request, [
//            'email' => 'required',
//            'password' => 'required',
//        ]);
//
//        if ($this->hasTooManyLoginAttempts($request)) {
//            $this->fireLockoutEvent($request);
//            return response()->json(['error' => 'Too many logins'], 400);
//        }
//
//        try {
//            if (! $token = JWTAuth::attempt($credentials)) {
//                return response()->json(['error' => 'Invalid Login Details'], 401);
//            }
//        } catch (JWTException $e) {
//            // something went wrong
//            $this->incrementLoginAttempts($request);
//            return response()->json(['error' => 'Could Not Create Token'], 500);
//        }
//
//        // if no errors are encountered we can return a JWT
//        return response()->json(compact('token'));
    }
}
