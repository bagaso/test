<?php

namespace App\Console\Commands;

use App\Jobs\JobVpnMonitorUser;
use App\SiteSettings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

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
        if(Schema::hasTable('site_settings') && SiteSettings::where('id', 1)->exists()) {
            $ctr = 0;
            $monitoruser_worker = array('monitor_user-1', 'monitor_user-2', 'monitor_user-3');
            $db_settings = SiteSettings::find(1);
            $servers = \App\VpnServer::has('users')->get();
            foreach ($servers as $server) {
                $job = (new JobVpnMonitorUser($server->id))->onConnection($db_settings->settings['queue_driver'])->onQueue($monitoruser_worker[$ctr]);
                dispatch($job);
                if($ctr==0)
                    $ctr=1;
                else if($ctr==1)
                    $ctr=2;
                else
                    $ctr = 0;
            }
        }
    }
}
