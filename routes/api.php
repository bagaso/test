<?php

use App\OnlineUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

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

Route::get('/account', function () {
    $permission['is_admin'] = auth()->user()->isAdmin();
    $permission['update_account'] = auth()->user()->can('update-account');
    $permission['manage_user'] = auth()->user()->can('manage-user');
    return response()->json([
        'profile'=> auth()->user(),
        'permission' => $permission,
        'vpn_session' => \App\OnlineUser::with('vpnserver')->where('user_id', auth()->user()->id)->get()
    ], 200);
})->middleware('auth:api');

Route::get('/vpn_auth', function (Request $request) {
    try {
        $username = $request->username;
        $password = $request->password;
        $server_key = $request->server_key;

        if (!preg_match("/^[a-z0-9_]+$/",$username)) {
            return '0';
        }

        $server = \App\VpnServer::where('server_key', $server_key)->firstorfail();

        $account = \App\User::where('username', $username)->firstorfail();

        if($server->users()->where('username', $username)->count() == 0 && Hash::check($password, $account->password)) {
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
        if(!$server->is_active) {
            return '0';
        }

        $user = \App\User::where('username', $username)->firstorfail();

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
//        $vpn_history = new \App\VpnHistory;
//        $vpn_history->user_id = $user_delete->id;
//        $vpn_history->server_name = $server->server_name;
//        $vpn_history->server_ip = $server->server_ip;
//        $vpn_history->server_domain = $server->server_domain;
//        $vpn_history->byte_sent = floatval($bytes_sent);
//        $vpn_history->byte_received = floatval($bytes_received);
//        $vpn_history->session_start = \Carbon\Carbon::parse($vpn->getOriginal('created_at'));
//        $vpn_history->save();
        $user_delete->vpn()->where('vpn_server_id', $server->id)->delete();
        return '1';
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
        return '0';
    }
});

Route::get('/account/profile', 'Account\AccountController@index');
Route::post('/account/profile', 'Account\AccountController@update');

Route::get('/account/security', 'Account\SecurityController@index');
Route::post('/account/security', 'Account\SecurityController@update');

Route::get('/account/extend-duration', 'Account\ExtendDurationController@index');
Route::post('/account/extend-duration', 'Account\ExtendDurationController@extend');

Route::get('/account/transfer-credits', 'Account\TransferCreditsController@index');
Route::post('/account/transfer-credits', 'Account\TransferCreditsController@transfer');

Route::get('/account/vpn_status', 'Account\VpnStatusController@index');
Route::post('/account/vpn_disconnect', 'Account\VpnStatusController@disconnect');

Route::get('/voucher/generate', 'VoucherController@generateVoucherIndex');
Route::post('/voucher/generate', 'VoucherController@generate');

Route::get('/voucher/apply', 'VoucherController@applyVoucherIndex');
Route::post('/voucher/apply', 'VoucherController@applyVoucher');
Route::post('/voucher/delete-voucher', 'VoucherController@deleteVoucher');

Route::get('/manage-user/all', 'ManageUser\ListUserAllController@index');
Route::get('/manage-user/ultimate', 'ManageUser\ListUserUltimateController@index');
Route::get('/manage-user/premium', 'ManageUser\ListUserPremiumController@index');
Route::get('/manage-user/reseller', 'ManageUser\ListUserResellerController@index');
Route::get('/manage-user/client', 'ManageUser\ListUserClientController@index');
Route::get('/manage-user/trash', 'ManageUser\ListUserTrashController@index');

Route::get('/manage-user/profile/{id}', 'ManageUser\UserProfileController@index');
Route::post('/manage-user/profile/{id}', 'ManageUser\UserProfileController@updateProfile');

Route::get('/manage-user/security/{id}', 'ManageUser\UserSecurityController@index');
Route::post('/manage-user/security/{id}', 'ManageUser\UserSecurityController@updateSecurity');

Route::get('/manage-user/permission/{id}', 'ManageUser\UserPermissionController@index');
Route::get('/manage-user/permission/{id}/{p_code}', 'ManageUser\UserPermissionController@updatePermission');

Route::get('/manage-user/duration/{id}', 'ManageUser\UserDurationController@index');
Route::post('/manage-user/duration/{id}', 'ManageUser\UserDurationController@updateDuration');

Route::get('/manage-user/credits/{id}', 'ManageUser\UserCreditController@index');
Route::post('/manage-user/credits/{id}', 'ManageUser\UserCreditController@updateCredits');

Route::get('/manage-user/voucher/{id}', 'ManageUser\UserVoucherController@index');
Route::post('/manage-user/voucher/{id}', 'ManageUser\UserVoucherController@applyVoucher');
Route::post('/manage-user/voucher/{id}/delete', 'ManageUser\UserVoucherController@deleteVoucher');

Route::post('/manage-user/vpn-session/{id}', 'ManageUser\UserDisconnectVpn@index');

Route::post('/distributor', 'DistributorController@index');

Route::get('/online-users', 'OnlineUsersController@index');
Route::post('/online-users', 'OnlineUsersController@searchOnlineUser');
Route::post('/online-users/disconnect-user', 'OnlineUsersController@disconnectVpn');

Route::get('/news-and-updates', 'NewsAndUpdates\ListController@index');
Route::post('/news-and-updates/delete', 'NewsAndUpdates\ListController@deletePost');
Route::post('/news-and-updates/pin-post', 'NewsAndUpdates\ListController@pinPost');
Route::post('/news-and-updates/unpin-post', 'NewsAndUpdates\ListController@unPinPost');
Route::post('/news-and-updates/item', 'NewsAndUpdates\ItemController@index');
Route::get('/news-and-updates/edit/{id}', 'NewsAndUpdates\EditItemController@index');
Route::post('/news-and-updates/edit/{id}', 'NewsAndUpdates\EditItemController@update');
Route::get('/news-and-updates-create', 'NewsAndUpdates\CreateController@index');
Route::post('/news-and-updates-create', 'NewsAndUpdates\CreateController@create');

Route::get('/manage-user/create', 'ManageUser\CreateUserController@index');
Route::post('/manage-user/create', 'ManageUser\CreateUserController@create');

Route::post('/manage-user/delete-client', 'ManageUser\ListUserClientController@deleteUsers');
Route::post('/manage-user/delete-reseller', 'ManageUser\ListUserResellerController@deleteUsers');
Route::post('/manage-user/delete-premium', 'ManageUser\ListUserPremiumController@deleteUsers');
Route::post('/manage-user/delete-ultimate', 'ManageUser\ListUserUltimateController@deleteUsers');
Route::post('/manage-user/delete-all', 'ManageUser\ListUserAllController@deleteUsers');

Route::post('/manage-user/client-update-userpackage', 'ManageUser\ListUserClientController@updateUserPackage');
Route::post('/manage-user/reseller-update-userpackage', 'ManageUser\ListUserResellerController@updateUserPackage');
Route::post('/manage-user/premium-update-userpackage', 'ManageUser\ListUserPremiumController@updateUserPackage');
Route::post('/manage-user/ultimate-update-userpackage', 'ManageUser\ListUserUltimateController@updateUserPackage');
Route::post('/manage-user/all-update-userpackage', 'ManageUser\ListUserAllController@updateUserPackage');

Route::post('/manage-user/client-update-usergroup', 'ManageUser\ListUserClientController@updateUserGroup');
Route::post('/manage-user/reseller-update-usergroup', 'ManageUser\ListUserResellerController@updateUserGroup');
Route::post('/manage-user/premium-update-usergroup', 'ManageUser\ListUserPremiumController@updateUserGroup');
Route::post('/manage-user/ultimate-update-usergroup', 'ManageUser\ListUserUltimateController@updateUserGroup');
Route::post('/manage-user/all-update-usergroup', 'ManageUser\ListUserAllController@updateUserGroup');

Route::post('/manage-user/client-update-status', 'ManageUser\ListUserClientController@updateUserStatus');
Route::post('/manage-user/reseller-update-status', 'ManageUser\ListUserResellerController@updateUserStatus');
Route::post('/manage-user/premium-update-status', 'ManageUser\ListUserPremiumController@updateUserStatus');
Route::post('/manage-user/ultimate-update-status', 'ManageUser\ListUserUltimateController@updateUserStatus');
Route::post('/manage-user/all-update-status', 'ManageUser\ListUserAllController@updateUserStatus');

Route::post('/manage-user/user-restore', 'ManageUser\ListUserTrashController@restoreUser');
Route::post('/manage-user/user-force-delete', 'ManageUser\ListUserTrashController@forceDeleteUser');

Route::get('/vpn-server/add', 'VpnServer\AddServerController@index');
Route::post('/vpn-server/add', 'VpnServer\AddServerController@addServer');
Route::get('/vpn-server/list', 'VpnServer\ListServerController@index');
Route::post('/vpn-server/delete-server', 'VpnServer\ListServerController@deleteServer');
Route::post('/vpn-server/server-up', 'VpnServer\ListServerController@upServer');
Route::post('/vpn-server/server-down', 'VpnServer\ListServerController@downServer');
Route::post('/vpn-server/server-free', 'VpnServer\ListServerController@freeServer');
Route::post('/vpn-server/server-premium', 'VpnServer\ListServerController@premiumServer');
Route::get('/vpn-server/server-info/{id}', 'VpnServer\ServerInfoController@index');
Route::post('/vpn-server/server-info/{id}', 'VpnServer\ServerInfoController@updateServer');
Route::get('/vpn-server/generatekey', 'VpnServer\ServerInfoController@generatekey');
