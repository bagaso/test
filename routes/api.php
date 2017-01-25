<?php

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/account', function (Request $request) {
    $account = $request->user();
    return $account;
})->middleware('auth:api');

Route::post('/account/update', 'UpdateAccount@update');
Route::post('/account/password', 'UpdateAccountPassword@update');
Route::get('/manage-user/all', 'ManageUserController@allUser');
Route::get('/manage-user/ultimate', 'ManageUserController@ultimateUser');
Route::get('/manage-user/premium', 'ManageUserController@premiumUser');
Route::get('/manage-user/reseller', 'ManageUserController@resellerUser');
Route::get('/manage-user/client', 'ManageUserController@clientUser');
Route::get('/manage-user/profile/{id}', 'ManageUserController@viewProfile');
Route::post('/manage-user/profile/{id}', 'ManageUserController@updateProfile');
Route::get('/manage-user/security/{id}', 'ManageUserController@viewProfile');
Route::post('/manage-user/security/{id}', 'ManageUserController@updateSecurity');
Route::get('/manage-user/create', 'ManageUserController@viewCreate');
Route::post('/manage-user/create', 'ManageUserController@create');
