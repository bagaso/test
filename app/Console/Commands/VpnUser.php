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
        $servers = \App\VpnServer::where('is_active', 1)->get();
        foreach ($servers as $server) {
            $job = (new JobVpnUpdateUsers($server->id))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('vpnupdateusers-1');
            dispatch($job);
        }
    }
    
}
