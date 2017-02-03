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
        $servers = \App\VpnServer::has('users')->where('is_active', 1)->get();
        foreach ($servers as $server) {
            $job = (new JobVpnUpdateUsers($server))->delay(\Carbon\Carbon::now()->addSeconds(5))->onQueue('vpnupdateusers');
            dispatch($job);
        }
//        $servers = \App\VpnServer::where('is_active', 1)->get();
//        foreach ($servers as $server) {
//            $logs = $this->parseLog('http://' . strtolower($server->server_domain) . '/logs/logs.log', 'tcp');
//            foreach($logs as $log)
//            {
//                $user = \App\User::where('username', $log['CommonName'] ? $log['CommonName'] : 'UNDEF')->first();
//                if($user->count() > 0 && $user->onlineuser->count() > 0) {
//                    $user->onlineuser->byte_sent = intval($log['BytesSent']) ? intval($log['BytesSent']) : 0;
//                    $user->onlineuser->byte_received = intval($log['BytesReceived']) ? intval($log['BytesReceived']) : 0;
//                    $user->onlineuser->touch();
//                    $user->onlineuser->save();
//                }
//            }
//        }
    }
    
}
