<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobVpnUpdateUsers implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    protected $server;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(\App\VpnServer $server)
    {
        $this->server = $server;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logs = $this->parseLog('http://' . strtolower($this->server->server_domain) . '/logs/logs.log', 'tcp');
        foreach($logs as $log)
        {
            $users = $this->server->users->where('username', $log['CommonName'] ? $log['CommonName'] : 'UNDEF');
            foreach($users as $user)
            {
                if($user->vpn) {
                    $user->vpn->byte_sent = intval($log['BytesSent']) ? intval($log['BytesSent']) : 0;
                    $user->vpn->byte_received = intval($log['BytesReceived']) ? intval($log['BytesReceived']) : 0;
                    $user->vpn->touch();
                    $user->vpn->save();
                } else {
                    $job = (new JobVpnDisconnectUser($user->username, $this->server->server_ip, $this->server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                    dispatch($job);
                }
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
