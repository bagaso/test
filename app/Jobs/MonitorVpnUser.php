<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MonitorVpnUser implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $online_users = \App\OnlineUser::all();
        foreach ($online_users as $online_user) {
            Log:info('I was here. ' . $online_user->user);
            if(!$online_user->user->isAdmin()) {
                $current = \Carbon\Carbon::now();
                $dt = \Carbon\Carbon::parse($online_user->user->getOriginal('expired_at'));
                if($online_user->user->status_id != 1 || $current->gte($dt)) {
                    $job = (new DisconnectVpnUser($online_user))->delay(\Carbon\Carbon::now()->addSeconds(10))->onQueue('disconnectvpnuser');
                    dispatch($job);
//                    $socket = @fsockopen($online_user->vpnserver->server_domain, '8000', $errno, $errstr);
//                    if($socket)
//                    {
//                        //echo "Connected";
//                        //fputs($socket, "smartyvpn\n");
//                        @fputs($socket, "kill {$online_user->user->username}\n");
//                        @fputs($socket, "quit\n");
//                    }
//                    @fclose($socket);
                }
            }
        }
    }
}
