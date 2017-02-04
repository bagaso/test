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
        $delete_idle = \App\OnlineUser::where('updated_at', '<=', \Carbon\Carbon::now()->subMinutes(2));
        foreach ($delete_idle as $online_user) {
            $job = (new JobVpnDisconnectUser($online_user->user->username, $online_user->server_ip, $online_user->server_port))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('disconnectvpnuser');
            dispatch($job);
        }
        $delete_idle->delete();
    }
}
