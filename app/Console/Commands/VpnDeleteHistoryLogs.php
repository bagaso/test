<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VpnDeleteHistoryLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpn:deletehistory';

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
        $delete_history = \App\VpnHistory::where('created_at', '<=', \Carbon\Carbon::now()->subMinutes(5));
        $delete_history->delete();
    }
}
