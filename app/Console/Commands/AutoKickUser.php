<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoKickUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autokickuser';

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
        $online_users = \App\OnlineUser::all();
        foreach ($online_users as $online_user) {
            if(!$online_user->user->isAdmin()) {
                $current = \Carbon\Carbon::now();
                $dt = \Carbon\Carbon::parse($online_user->user->getOriginal('expired_at'));
                if($online_user->user->status_id != 1 || $current->gte($dt)) {
                    $socket = fsockopen('sg2.smartyvpn.com', '8000', $errno, $errstr);
                    if($socket)
                    {
                        echo $online_user->user->username;
                        //echo "Connected";
                        //fputs($socket, "smartyvpn\n");
                        fputs($socket, "kill {$online_user->user->username}\n");
                        fputs($socket, "quit\n");
                    }
                    fclose($socket);

                }
            }
        }
    }
}
