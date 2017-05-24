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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/updates/android-updates.json', function() {
    $json = \App\JsonUpdate::findorfail(1);
    return $json->json;
});

Route::get('/updates/gui-updates.json', function() {
    $json = \App\JsonUpdate::findorfail(2);
    return $json->json;
});
