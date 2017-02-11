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

Route::get('/wew', function () {

//    $server = \App\VpnServer::with('users', 'online_users')->findorfail(1);
//    return $server->online_users->count();

//    $vpn_user = \App\User::find(3);
//    return $vpn_user->vpn->with('vpnserver');
//    $vpn_user->byte_received = 2;
////    $vpn_user->touch();
//    $vpn_user->save();

        //echo $a[mt_rand(0, count($a) - 1)];
//    $vpn = $user_delete->vpn()->where('vpn_server_id', 1)->firstorfail();
//    echo $vpn->delete();
//    $server = \App\VpnServer::findorfail(1);
//    foreach ($server->users as $online_user) {
//        echo $online_user->vpn()->where('vpn_server_id', 1)->firstorfail()->data_available;
//    }
    //return $account->users->firstorfail();
});

Route::get('/account', function () {
    return auth()->user();
})->middleware('auth:api');

Route::get('/vpn_auth', function (Request $request) {
    try {
        $username = $request->username;
        $password = $request->password;
        $server_key = $request->server_key;
        $server = \App\VpnServer::where('server_key', $server_key)->firstorfail();

        if(Auth::attempt(['username' => $username, 'password' => $password])) {
            Auth::user()->timestamps = false;
            Auth::logout();
            return '1';
        }
        else
            return '0';
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
        return '0';
    }
});

Route::get('/vpn_auth_connect', function (Request $request) {
    try {
        $username = trim($request->username);
        $server_key = trim($request->server_key);

        if($username == '' || $server_key == '') return '0';

        $server = \App\VpnServer::where('server_key', $server_key)->firstorfail();
        $user = \App\User::where('username', $username)->firstorfail();
        if(!$server->is_active || $user->vpn()->where('vpn_server_id', $server->id)->count() > 0) {
            return '0';
        }

        $current = Carbon::now();
        $dt = Carbon::parse($user->getOriginal('expired_at'));

        if($user->isAdmin() || $user->isActive()) {
            if(!$user->isAdmin()) {
                if($user->vpn->count() >= $user->vpn_session) {
                    return '0';
                }
                if($current->gte($dt)) {
                    if(!$server->free_user || $user->consumable_data < 1) {
                        return '0';
                    }
                }
            }

            $vpn = new OnlineUser;
            $vpn->user_id = $user->id;
            $vpn->vpn_server_id = $server->id;
            $vpn->byte_sent = 0;
            $vpn->byte_received = 0;
            if(!$user->isAdmin() && $current->gte($dt)) {
                $vpn->data_available = $user->consumable_data;
            }
            if($vpn->save()) {
                return '1';
            }
            return '0';
        }
        return '0';
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
        return '0';
    }
});

Route::get('/vpn_auth_disconnect', function (Request $request) {
    try {
        $username = trim($request->username);
        $server_key = trim($request->server_key);
        $bytes_sent = trim($request->bytes_sent);
        $bytes_received = trim($request->bytes_received);
        $server = \App\VpnServer::where('server_key', $server_key)->firstorfail();
        $user_delete = $server->users()->where('username', $username)->firstorfail();

        $current = \Carbon\Carbon::now();
        $dt = \Carbon\Carbon::parse($user_delete->getOriginal('expired_at'));

        $vpn = $user_delete->vpn()->where('vpn_server_id', $server->id)->firstorfail();
        if(!$user_delete->isAdmin() && $current->gte($dt) && $vpn->data_available > 0) {
            $data = $vpn->data_available - floatval($bytes_sent);
            $user_delete->consumable_data = ($data >= 0) ? $data : 0;
            $user_delete->timestamps = false;
            $user_delete->save();
        }
        $vpn_history = new \App\VpnHistory;
        $vpn_history->server_name = $server->server_name;
        $vpn_history->server_ip = $server->server_ip;
        $vpn_history->server_domain = $server->server_domain;
        $vpn_history->byte_sent = floatval($bytes_sent);
        $vpn_history->byte_received = floatval($bytes_received);
        $vpn_history->session_start = \Carbon\Carbon::parse($vpn->getOriginal('created_at'));
        $vpn_history->save();
        $user_delete->vpn()->where('vpn_server_id', $server->id)->delete();
        return '1';
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
        return '0';
    }
});


Route::post('/account/update', 'UpdateAccount@update');
Route::post('/account/password', 'UpdateAccountPassword@update');
Route::post('/account/vpn_user_disconnect', 'UpdateAccount@disconnectVpn');
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
Route::get('/manage-user/user-voucher/{id}', 'ManageUserController@userVoucher');
Route::get('/manage-user/create', 'ManageUserController@viewCreate');
Route::post('/manage-user/create', 'ManageUserController@create');
