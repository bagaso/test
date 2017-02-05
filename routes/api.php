<?php

use App\OnlineUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

Route::get('/wew', function (Request $request) {
    $account = \App\User::findorfail(99);
    return $account->vouchers()->where('code', '12345')->paginate(1);
});

Route::get('/account', function (Request $request) {
    $account = $request->user();
    return $account;
})->middleware('auth:api');

Route::get('/vpn_auth', function (Request $request) {
    $username = $request->username;
    $password = $request->password;
    if(Auth::attempt(['username' => $username, 'password' => $password])) {
        Auth::user()->timestamps = false;
        Auth::logout();
        return '1';
    }
    else
        return '0';
});

Route::get('/vpn_auth_connect', function (Request $request) {
    $username = trim($request->username);

    if($username == '') return '0';

    $user = \App\User::where('username', $username)->first();

    if(count($user) == 0) return '0';

    $current = Carbon::now();
    $dt = Carbon::parse($user->getOriginal('expired_at'));
    
    if($user->isAdmin() || ($user->status_id == 1 && $current->lte($dt))) {
        if($user->vpn) {
            return '0';
        }
        $server = \App\VpnServer::where('server_ip', $request->server_ip)->first();
        if(count($server) == 0 || !$server->is_active) {
            return '0';
        }
        $vpn = new OnlineUser();
        $vpn->user_id = $user->id;
        $vpn->vpn_server_id = $server->id;
        $vpn->server_ip = $server->server_ip;
        $vpn->server_port = $server->server_port;
        $vpn->byte_sent = 0;
        $vpn->byte_received = 0;
        if($vpn->save()) {
            return '1';
        }
        return '0';
    }
    return '0';
});

Route::get('/vpn_auth_disconnect', function (Request $request) {
    $username = trim($request->username);
    $delete = \App\User::where('username', $username)->first();
    if(count($delete) > 0 && $delete->vpn) {
            $delete->vpn->delete();
    }
    return '1';
});


Route::post('/account/update', 'UpdateAccount@update');
Route::post('/account/password', 'UpdateAccountPassword@update');
Route::get('/manage-user/all', 'ManageUserController@allUser');
Route::get('/manage-user/ultimate', 'ManageUserController@ultimateUser');
Route::get('/manage-user/premium', 'ManageUserController@premiumUser');
Route::get('/manage-user/reseller', 'ManageUserController@resellerUser');
Route::get('/manage-user/client', 'ManageUserController@clientUser');
Route::get('/manage-user/profile/{id}', 'ManageUserController@viewProfile');
Route::post('/manage-user/profile/{id}', 'ManageUserController@updateProfile');
Route::get('/manage-user/security/{id}', 'ManageUserController@viewSecurity');
Route::post('/manage-user/security/{id}', 'ManageUserController@updateSecurity');
Route::get('/manage-user/permission/{id}', 'ManageUserController@viewPermission');
Route::get('/manage-user/permission/{id}/{p_code}', 'ManageUserController@updatePermission');
Route::get('/manage-user/duration/{id}', 'ManageUserController@viewDuration');
Route::post('/manage-user/duration/{id}', 'ManageUserController@updateDuration');
Route::get('/manage-user/credits/{id}', 'ManageUserController@viewCredits');
Route::post('/manage-user/credits/{id}', 'ManageUserController@updateCredits');
Route::get('/manage-user/voucher/{id}', 'ManageUserController@viewVoucher');
Route::post('/manage-user/voucher/{id}', 'ManageUserController@applyVoucher');
Route::get('/manage-user/create', 'ManageUserController@viewCreate');
Route::post('/manage-user/create', 'ManageUserController@create');
