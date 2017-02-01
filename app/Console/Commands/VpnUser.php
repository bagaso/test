<?php

namespace App\Console\Commands;

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
        $homepage = $this->parseLog('http://sg2.smartyvpn.com/logs/logs.log', 'tcp');
        print_r($homepage);
    }

    public function parseLog($log, $proto) {
        global $uid, $ctr;
        $status = array();
        $handle = @fopen($log, "r");

        if($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);

                unset($match);

                //if (ereg("^Updated,(.+)", $buffer, $match)) {
                    //$status['updated'] = $match[1];
                //}

                if (preg_match("/^(.+),(\d+\.\d+\.\d+\.\d+\:\d+),(\d+),(\d+),(.+)$/", $buffer, $match)) {
                    if ($match[1] <> "Common Name") {
                        //      $cn = $match[1];

                        // for each remote ip:port because smarty doesnt
                        // like looping on strings in a section
                        $userlookup[$match[2]] = $uid;

                        $status['CommonName'] = $match[1];
                        $status['RealAddress'] = $match[2];
                        $status['BytesReceived'] = $match[3]; #sizeformat($match[3]);
                        $status['BytesSent'] = $match[4]; #sizeformat($match[4]);
                        $status['Since'] = $match[5];
                        $status['Proto'] = $proto;
                        $uid++; $ctr++;
                    }
                }

            }
            fclose($handle);
        }
        return $status;
    }
}
