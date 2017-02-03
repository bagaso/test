<?php

namespace App\Jobs;

use App\OnlineUser;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DisconnectVpnUser implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $onlineUser;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(OnlineUser $onlineUser)
    {
        $this->onlineUser = $onlineUser;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $socket = @fsockopen($this->onlineUser->vpnserver->server_domain, '8000', $errno, $errstr);
        if($socket)
        {
            //echo "Connected";
            //fputs($socket, "smartyvpn\n");
            @fputs($socket, "kill {$this->onlineUser->user->username}\n");
            @fputs($socket, "quit\n");
        }
        @fclose($socket);
    }
}
