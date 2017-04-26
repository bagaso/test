<?php

namespace App\Http\Controllers;

use App\Lang;
use App\SiteSettings;
use App\VpnServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicServerStatusController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    
    public function test()
    {
        try {
            if(Schema::hasTable('site_settings') && SiteSettings::where('id', 1)->exists()) {
                $db_settings = SiteSettings::find(1);

                $server = \App\VpnServer::findorfail(1);
                $logs = $this->parseLog($server->server_ip, 'tcp', $server->web_port);
                foreach($logs as $log)
                {
                    try {
                        $user = \App\User::with('user_package')->where('username', $log['CommonName'])->firstorfail();
                        $login_session = $user->vpn->count();
                        if($user->isAdmin() || $login_session >= 1 && $login_session <= intval($user->user_package->user_package['device'])) {
                            $vpn_user = $user->vpn()->where('vpn_server_id', $server->id);
                            $vpn_user->update(['byte_sent' => floatval($log['BytesSent']) ? floatval($log['BytesSent']) : 0, 'byte_received' => floatval($log['BytesReceived']) ? floatval($log['BytesReceived']) : 0]);
                            //$vpn_user->update(['byte_sent' => 0, 'byte_received' => 0]);
                            return '01';
                        } else {
                            return '1';
                        }
                    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
                        return '2';
                    }
                }

            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            //
        }
        return '22';
    }

    public function availableIp($host, $port, $timeout=3) {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if($fp) {
            return true;
        } else {
            return false;
        }
    }


    public function parseLog($ip, $proto, $port=80) {
        $status = array();
        $ctr = 0;
        $uid = 0;

        if($this->availableIp($ip, $port)) {
            $handle = @fopen('http://' . $ip . ':' . $port . '/logs/logs.log', "r");

            if($handle) {
                while (!@feof($handle)) {
                    $buffer = @fgets($handle, 4096);

                    unset($match);

                    //if (ereg("^Updated,(.+)", $buffer, $match)) {
                    //$status['updated'] = $match[1];
                    //}

                    if (preg_match("/^(.+),(\d+\.\d+\.\d+\.\d+\:\d+),(\d+),(\d+),(.+)$/", $buffer, $match)) {
                        if ($match[1] <> 'Common Name' && $match[1] <> 'UNDEF' && $match[1] <> 'client') {
                            //      $cn = $match[1];

                            // for each remote ip:port because smarty doesnt
                            // like looping on strings in a section
                            $userlookup[$match[2]] = $uid;

                            $status[$ctr]['CommonName'] = $match[1];
                            $status[$ctr]['RealAddress'] = $match[2];
                            $status[$ctr]['BytesReceived'] = $match[3]; #sizeformat($match[3]);
                            $status[$ctr]['BytesSent'] = $match[4]; #sizeformat($match[4]);
                            $status[$ctr]['Since'] = $match[5];
                            $status[$ctr]['Proto'] = $proto;
                            $uid++; $ctr++;
                        }
                    }

                }
                @fclose($handle);
            }
        }

        return $status;
    }
    
    public function index()
    {
        $db_settings = \App\SiteSettings::findorfail(1);

        $language = Lang::all()->pluck('name');

        if (!$db_settings->settings['enable_panel_login']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error'],
                'message' => 'Maintenance Mode.',
                'language' => $language,
            ], 401);
        }

        if (!$db_settings->settings['public_server_status']) {
            return response()->json([
                'site_options' => ['site_name' => $db_settings->settings['site_name'], 'sub_name' => 'Error', 'public_credit_distributors' => $db_settings->settings['public_credit_distributors'], 'public_online_users' => $db_settings->settings['public_online_users']],
                'message' => 'Please Login to access this page.',
                'language' => $language,
            ], 403);
        }

        $servers = VpnServer::with(['server_access', 'user_packages'])->select('id', 'server_name', 'server_access_id', 'limit_bandwidth', 'is_active')->withCount('online_users')->orderBy('server_name', 'asc')->get();

        $site_options['site_name'] = $db_settings->settings['site_name'];
        $site_options['sub_name'] = 'Server Status';
        $site_options['enable_panel_login'] = $db_settings->settings['enable_panel_login'];
        $site_options['public_online_users'] = $db_settings->settings['public_online_users'];
        $site_options['public_credit_distributors'] = $db_settings->settings['public_credit_distributors'];
        $site_options['public_server_status'] = $db_settings->settings['public_server_status'];

        return response()->json([
            'site_options' => $site_options,
            'language' => $language,
            'model' => $servers,
        ], 200);
    }
}
