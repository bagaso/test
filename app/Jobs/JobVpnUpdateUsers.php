<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class JobVpnUpdateUsers implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    protected $server_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($server_id)
    {
        $this->server_id = $server_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $server = \App\VpnServer::findorfail($this->server_id);
        $logs = $this->parseLog('http://' . strtolower($server->server_ip) . '/logs/logs.log', 'tcp');
        foreach($logs as $log)
        {
            try {
                $user = \App\User::where('username', $log['CommonName'])->firstorfail();
                $login_session = $user->vpn->count();
                if($user->isAdmin() || $login_session >= 1 && $login_session <= $user->vpn_session) {
                    $vpn_user = $user->vpn()->where('vpn_server_id', $this->server_id);
                    $vpn_user->update(['byte_sent' => floatval($log['BytesSent']) ? floatval($log['BytesSent']) : 0, 'byte_received' => floatval($log['BytesReceived']) ? floatval($log['BytesReceived']) : 0]);
                } else {
                    $job = (new JobVpnDisconnectUser($log['CommonName'], $server->server_ip, $server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                    dispatch($job);
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
                $job = (new JobVpnDisconnectUser($log['CommonName'], $server->server_ip, $server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                dispatch($job);
            }
        }
    }

    public function parseLog($log, $proto) {
        //global $uid, $ctr;
        $status = array();
        $ctr = 0;
        $uid = 0;
        $handle = @fopen($log, "r");

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
        return $status;
    }
    
}
