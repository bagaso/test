<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobVpnMonitorUser implements ShouldQueue
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
        foreach ($this->server->users as $user) {
            if(!$this->server->is_active) {
                $job = (new JobVpnDisconnectUser($user, $this->server))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                dispatch($job);
            } else if(!$user->isAdmin()) {
                $current = \Carbon\Carbon::now();
                $dt = \Carbon\Carbon::parse($user->getOriginal('expired_at'));
                if($user->status_id != 1 || $current->gte($dt)) {
                    $job = (new JobVpnDisconnectUser($user, $this->server))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                    dispatch($job);
                }
            }
        }
    }
}
