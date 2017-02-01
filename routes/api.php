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

    $user = App\User::where([['username', '=', $username]])->first();

    if(count($user) == 0) return '0';

    $current = Carbon::now();
    $dt = Carbon::parse($user->getOriginal('expired_at'));
    
    if($user->isAdmin() || ($user->status_id == 1 && $current->lte($dt))) {
        $new_online = new OnlineUser();
        if($new_online->create($request->all())) {
            return '1';
        }
        return '0';
    }
    return '0';
});

Route::get('/vpn_auth_disconnect', function (Request $request) {
    $username = trim($request->username);

    $delete = OnlineUser::where('username', '=', $username)->delete();
    $delete->save();

    return '1';
});


Route::get('/log', function () {

    $val = '';

    $fp = stream_socket_client("tcp://188.166.242.96:8000", $errno, $errstr, 30);
    if (!$fp) {
        echo "$errstr ($errno)<br />\n";
    } else {
        fwrite($fp, "status\r\n");
        $ctr = 0;
        while (!feof($fp)) {
            $val .= fgets($fp, 1024);
            if($ctr >= 20) {
                fclose($fp);
                break;
            }
            $ctr++;
        }
        echo $val;
        //fclose($fp);
    }
//    $socket = fsockopen('sg2.smartyvpn.com', '8000', $errno, $errstr);
//    $val = '';
//    if($socket)
//    {
//        $val =  "Connected";
//        //fputs($socket, "smartyvpn\n");
//        //fputs($socket, "kill {$row['user_name']}\n");
//        fputs($socket, "status\n");
//        echo fgets($socket, 4096);
//        fputs($socket, "quit\n");
//    }
//    fclose($socket);
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
Route::get('/manage-user/create', 'ManageUserController@viewCreate');
Route::post('/manage-user/create', 'ManageUserController@create');
