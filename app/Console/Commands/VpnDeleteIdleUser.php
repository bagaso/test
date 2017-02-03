<?php

namespace App\Console\Commands;

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
        $delete_idle = \App\OnlineUser::where('updated_at', '<=', \Carbon\Carbon::now()->subMinutes(5));
        $delete_idle->delete();
    }
}
