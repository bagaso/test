<?php

namespace App\Jobs;

use App\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Schema;

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
            if(Schema::hasTable('site_settings') && SiteSettings::where('id', 1)->exists()) {
                $db_settings = SiteSettings::find(1);
                $server = \App\VpnServer::findorfail($this->server_id);
                foreach ($server->users as $online_user) {
                    if($server->users()->where('username', $online_user->username)->count() > 1) {
                        $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                        dispatch($job);
                    }
                    if(!$server->is_active) {
                        $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                        dispatch($job);
                    } else if(!$online_user->isAdmin()) {
                        $current = \Carbon\Carbon::now();
                        $dt = \Carbon\Carbon::parse($online_user->getOriginal('expired_at'));
                        $vpn = $online_user->vpn()->where('vpn_server_id', $this->server_id)->firstorfail();
                        if ($online_user->status->id == 1 && $server->allowed_userpackage['package_1'] == 0) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if ($online_user->status->id == 2 && $server->allowed_userpackage['package_2'] == 0) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if ($online_user->status->id == 3 && $server->allowed_userpackage['package_3'] == 0) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if(!$online_user->isActive() || $online_user->vpn->count() > intval($online_user->user_package->user_package['device'])) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if(in_array($server->access, [1,2]) && $current->gte($dt)) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if($server->limit_bandwidth && $vpn->getOriginal('byte_sent') >= $vpn->data_available) {
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if($server->access == 0) {
                            if($current->lt($dt)) {
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                            $free_sessions = \App\VpnServer::where('access', 0)->get();
                            $free_ctr = 0;
                            foreach ($free_sessions as $free) {
                                if($free->users()->where('id', $online_user->id)->count() > 0) {
                                    $free_ctr += 1;
                                }
                            }
                            if($free_ctr > 1) {
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                        } else if($server->access == 2) {
                            $vip_sessions = \App\VpnServer::where('access', 2)->get();
                            $vip_ctr = 0;
                            foreach ($vip_sessions as $vip) {
                                if($vip->users()->where('id', $online_user->id)->count() > 0) {
                                    $vip_ctr += 1;
                                }
                            }
                            if($vip_ctr > 1) {
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                        }
                    }
                }
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            //
        }
    }
}
