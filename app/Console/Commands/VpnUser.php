<?php

namespace App\Console\Commands;

use App\Jobs\JobVpnUpdateUsers;
use Illuminate\Console\Command;

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
        $ctr = 0;
        $vpnupdate_worker = array('log_update-1', 'log_update-2');
        $servers = \App\VpnServer::where('is_active', 1)->get();
        foreach ($servers as $server) {
            $job = (new JobVpnUpdateUsers($server->id))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue($vpnupdate_worker[$ctr]);
            dispatch($job);
            if($ctr==0)
                $ctr=1;
            else
                $ctr=0;
        }
    }
    
}
