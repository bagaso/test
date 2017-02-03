<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobVpnDisconnectUser implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $user, $server;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(\App\User $user, \App\VpnServer $server)
    {
        $this->user = $user;
        $this->server = $server;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $socket = @fsockopen($this->server->server_domain, '8000', $errno, $errstr);
        if($socket)
        {
            //echo "Connected";
            //fputs($socket, "smartyvpn\n");
            @fputs($socket, "kill {$this->user->username}\n");
            @fputs($socket, "quit\n");
        }
        @fclose($socket);
    }
}
