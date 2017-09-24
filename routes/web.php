<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Hash;

Route::get('/updates/android-updates.json', function() {
    $json = \App\JsonUpdate::findorfail(1);
    return $json->json;
});

Route::get('/updates/gui-updates.json', function() {
    $json = \App\JsonUpdate::findorfail(2);
    return $json->json;
});

Route::get('/updates/android-ss-updates.json', function() {
    $json = \App\JsonUpdate::findorfail(3);
    return $json->json;
});

Route::get('/updates/gui-ss-updates.json', function() {
    $json = \App\JsonUpdate::findorfail(4);
    return $json->json;
});

Route::get('/pc/duration/{username}', function($username) {
    $account = \App\User::where('username', $username)->firstorfail();

    return response()->json([
            'duration' => $account->expired_at
    ], 200);

//    if(Hash::check($password, $account->password)) {
//        return response()->json([
//            'duration' => $account->expired_at
//        ], 200);
//    }
});

Route::get('/android/duration/{username}', function($username) {
    $account = \App\User::where('username', $username)->firstorfail();

    if($account->expired_at == 'No Limit') {
        return response()->json([
            'premium' => -1,
            'vip' => null,
        ], 200);
    } else {
        return response()->json([
            'premium' => Carbon\Carbon::now()->gte(Carbon\Carbon::parse($account->getOriginal('expired_at'))) ? 0 : Carbon\Carbon::parse($account->getOriginal('expired_at'))->diffInSeconds(Carbon\Carbon::now()),
            'vip' => null,
        ], 200);
    }

//    if(Hash::check($password, $account->password)) {
//    }
});

Route::get('/android/upline/{username}', function($username) {
    $account = \App\User::where('username', $username)->firstorfail();

    return response()->json([
        'fullname' => $account->upline->fullname,
        'email' => $account->upline->email,
        'contact' => $account->upline->contact,
    ], 200);
});


