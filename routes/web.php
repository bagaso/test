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

Route::get('/pc/duration/{username}/{password}', function($username, $password) {
    $account = \App\User::where('username', $username)->firstorfail();

    if(Hash::check($password, $account->password)) {
        return response()->json([
            'duration' => $account->expired_at
        ], 200);
    }
});

Route::get('/android/duration/{username}/{password}', function($username, $password) {
    $account = \App\User::where('username', $username)->firstorfail();

    if(Hash::check($password, $account->password)) {
        return response()->json([
            'premium' => $account->expired_at == 'No Limit' ? -1 : Carbon\Carbon::parse($account->getOriginal('expired_at'))->timestamp,
            'vip' => null,
        ], 200);
    }
});
