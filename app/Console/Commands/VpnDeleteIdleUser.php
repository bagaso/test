<?php

namespace App\Console\Commands;

use App\Jobs\JobVpnDisconnectUser;
use App\SiteSettings;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VpnDeleteIdleUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpn:deleteidle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(Schema::hasTable('site_settings') && SiteSettings::where('id', 1)->exists()) {
            $db_settings = SiteSettings::find(1);
            
            $delete_idle = \App\OnlineUser::with(['user', 'vpnserver'])->where('updated_at', '<=', \Carbon\Carbon::now()->subMinutes(5));
            foreach ($delete_idle->get() as $online_user) {
                try {
                    $user = User::findorfail($online_user->user_id);

                    $job = (new JobVpnDisconnectUser($user->username, $online_user->vpnserver->server_ip, $online_user->vpnserver->server_port))->onConnection($db_settings->settings['queue_driver'])->onQueue('disconnect_user');
                    dispatch($job);

                    $user->timestamps = false;
                    if(!$user->isAdmin() && $online_user->vpnserver->limit_bandwidth && $online_user->data_available > 0) {
                        $data = doubleval($online_user->data_available) - doubleval($online_user->byte_sent);
                        $user->consumable_data = ($data >= 0) ? $data : 0;
                        $user->save();
                    }
                    $user->lifetime_bandwidth = doubleval($user->lifetime_bandwidth) + doubleval($online_user->byte_sent);
                    $user->save();
//            $vpn_history = new \App\VpnHistory;
//            $vpn_history->user_id = $online_user->user->id;
//            $vpn_history->server_name = $online_user->vpnserver->server_name;
//            $vpn_history->server_ip = $online_user->vpnserver->server_ip;
//            $vpn_history->server_domain = $online_user->vpnserver->server_domain;
//            $vpn_history->byte_sent = floatval($online_user->byte_sent);
//            $vpn_history->byte_received = floatval($online_user->byte_received);
//            $vpn_history->session_start = \Carbon\Carbon::parse($online_user->getOriginal('created_at'));
//            $vpn_history->save();
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
                    //
                }
            }
            $delete_idle->delete();
        }
    }
}
