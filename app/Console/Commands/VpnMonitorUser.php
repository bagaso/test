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
        $ctr = 0;
        $vpnmonitor_worker = array('monitorvpnuser-1', 'monitorvpnuser-2');
        $servers = \App\VpnServer::has('users')->where('is_active', 1)->get();
        foreach ($servers as $server) {
            $job = (new JobVpnMonitorUser($server->id))->delay(Carbon::now()->addSeconds(5))->onQueue($vpnmonitor_worker[$ctr]);
            dispatch($job);
            if($ctr==0)
                $ctr=1;
            else
                $ctr=0;
        }
    }
}
