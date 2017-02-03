<?php

namespace App\Console\Commands;

use App\Jobs\JobVpnMonitorUser;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VpnMonitorUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpn:monitoruser';

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
        $servers = \App\VpnServer::where('is_active', 1)->get();
        foreach ($servers as $server) {
            if($server->online_users->count() > 0) {
                $job = (new JobVpnMonitorUser($server->online_users))->delay(Carbon::now()->addSeconds(5))->onQueue('monitorvpnuser');
                dispatch($job);
            }
        }
//        $online_users = \App\OnlineUser::all();
//        if(count($online_users) > 0) {
//            $job = (new JobVpnMonitorUser($online_users))->delay(Carbon::now()->addSeconds(5))->onQueue('monitorvpnuser');
//            dispatch($job);
//        }
    }
}
