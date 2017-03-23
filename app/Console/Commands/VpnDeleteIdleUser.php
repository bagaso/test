<?php

namespace App\Console\Commands;

use App\Jobs\JobVpnDisconnectUser;
use Illuminate\Console\Command;

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
        $delete_idle = \App\OnlineUser::with(['user', 'vpnserver'])->where('updated_at', '<=', \Carbon\Carbon::now()->subMinutes(5));
        foreach ($delete_idle->get() as $online_user) {
            $job = (new JobVpnDisconnectUser($online_user->user->username, $online_user->vpnserver->server_ip, $online_user->vpnserver->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
            dispatch($job);
            if(!$online_user->user->isAdmin() && $online_user->data_available > 0) {
                $online_user->user->timestamps = false;
                $data = $online_user->data_available - floatval($online_user->byte_sent);
                $online_user->user->consumable_data = ($data >= 0) ? $data : 0;
                $online_user->user->save();
            }
//            $vpn_history = new \App\VpnHistory;
//            $vpn_history->user_id = $online_user->user->id;
//            $vpn_history->server_name = $online_user->vpnserver->server_name;
//            $vpn_history->server_ip = $online_user->vpnserver->server_ip;
//            $vpn_history->server_domain = $online_user->vpnserver->server_domain;
//            $vpn_history->byte_sent = floatval($online_user->byte_sent);
//            $vpn_history->byte_received = floatval($online_user->byte_received);
//            $vpn_history->session_start = \Carbon\Carbon::parse($online_user->getOriginal('created_at'));
//            $vpn_history->save();
        }
        $delete_idle->delete();
    }
}
