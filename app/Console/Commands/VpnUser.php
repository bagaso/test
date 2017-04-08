<?php

namespace App\Console\Commands;

use App\Jobs\JobVpnUpdateUsers;
use App\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VpnUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpnuser';

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
            $logupdate_worker = array('log_update-1', 'log_update-2');
            $db_settings = SiteSettings::find(1);
            $servers = \App\VpnServer::where('is_active', 1)->get();
            foreach ($servers as $server) {
                $job = (new JobVpnUpdateUsers($server->id))->onConnection($db_settings->settings['queue_driver'])->onQueue($logupdate_worker[$ctr]);
                dispatch($job);
                if($ctr==0)
                    $ctr=1;
                else
                    $ctr=0;
            }
        }

    }
    
}
