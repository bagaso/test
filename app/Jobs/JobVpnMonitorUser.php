<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobVpnMonitorUser implements ShouldQueue
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
        try {
            $server = \App\VpnServer::findorfail($this->server_id);
            foreach ($server->users as $online_user) {
                if(!$server->is_active) {
                    $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                    dispatch($job);
                } else if(!$online_user->isAdmin()) {
                    $current = \Carbon\Carbon::now();
                    $dt = \Carbon\Carbon::parse($online_user->getOriginal('expired_at'));
                    if(!$online_user->isActive() || $online_user->vpn->count() > $online_user->vpn_session) {
                        $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                        dispatch($job);
                    } else if($current->gte($dt)) {
                        $vpn = $online_user->vpn()->where('vpn_server_id', $this->server_id)->firstorfail();
                        if(!$server->free_user || $vpn->getOriginal('byte_sent') >= $vpn->data_available) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
                            dispatch($job);
                        }
                    }
                }
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
        }
    }
}
