<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobVpnMonitorUser implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    protected $onlineUsers;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($onlineUsers)
    {
        $this->onlineUsers = $onlineUsers;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->onlineUsers as $online_user) {
            if(!$online_user->user->isAdmin()) {
                $current = \Carbon\Carbon::now();
                $dt = \Carbon\Carbon::parse($online_user->user->getOriginal('expired_at'));
                if($online_user->user->status_id != 1 || $current->gte($dt)) {
                    $job = (new JobVpnDisconnectUser($online_user))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                    dispatch($job);
                }
            }
        }
    }
}
