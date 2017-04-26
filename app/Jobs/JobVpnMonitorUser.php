<?php

namespace App\Jobs;

use App\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
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
                $server = \App\VpnServer::with('server_access')->findorfail($this->server_id);
                foreach ($server->users as $online_user) {
                    if($server->users()->where('username', $online_user->username)->count() > 1) {
                        Log::info('Same Server Error.');
                        //$job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                        //dispatch($job);
                    }
                    if(!$server->is_active) {
                        Log::info('Server is Down.');
                        $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                        dispatch($job);
                    } else if(!$online_user->isAdmin()) {
                        $current = \Carbon\Carbon::now();
                        $dt = \Carbon\Carbon::parse($online_user->getOriginal('expired_at'));
                        $vpn = $online_user->vpn()->where('vpn_server_id', $this->server_id)->firstorfail();
                        if(!in_array($online_user->user_package->id, json_decode($server->user_packages->pluck('id')))) {
                            Log::info('User Package is not allowed');
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if(!$online_user->isActive() || $online_user->vpn->count() > intval($online_user->user_package->user_package['device'])) {
                            Log::info('Device usage error.');
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if($server->server_access->config['paid'] && $current->gte($dt)) {
                            Log::info('User is expired.');
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if($server->limit_bandwidth && $vpn->getOriginal('byte_sent') >= $vpn->data_available) {
                            Log::info('Bandwidth limit reached.');
                            $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                            dispatch($job);
                        } else if(!$server->server_access->config['paid']) {
                            if($current->lt($dt)) {
                                Log::info('Paid user cannot enter free server.');
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                            $free_sessions = \App\VpnServer::where('server_access_id', 1)->get();
                            $free_ctr = 0;
                            foreach ($free_sessions as $free) {
                                if($free->users()->where('id', $online_user->id)->count() > 0) {
                                    $free_ctr += 1;
                                }
                            }
                            if(!$server->server_access->config['multi_device'] && $free_ctr > 1) {
                                Log::info('a');
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                            if($free_ctr > $server->server_access->config['max_device']) {
                                Log::info('b');
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                        } else if($server->server_access->id == 3) {
                            $vip_sessions = \App\VpnServer::where('server_access_id', 3)->get();
                            $vip_ctr = 0;
                            foreach ($vip_sessions as $vip) {
                                if($vip->users()->where('id', $online_user->id)->count() > 0) {
                                    $vip_ctr += 1;
                                }
                            }
                            if(!$server->server_access->config['multi_device'] && $vip_ctr > 1) {
                                Log::info('c');
                                $job = (new JobVpnDisconnectUser($online_user->username, $server->server_ip, $server->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                                dispatch($job);
                            }
                            if($vip_ctr > $server->server_access->config['max_device']) {
                                Log::info('d');
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
