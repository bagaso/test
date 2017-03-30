<?php

namespace App\Console;

use \App\SiteSettings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\VpnUser',
        'App\Console\Commands\VpnDeleteIdleUser',
        'App\Console\Commands\VpnMonitorUser',
        'App\Console\Commands\VpnResetData',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('vpnuser')->everyMinute();
        $schedule->command('vpn:deleteidle')->everyMinute();
        $schedule->command('vpn:monitoruser')->everyMinute();
        if(Schema::hasTable('site_settings') && SiteSetting::where('id', 1)->exists()) {
            $site_settings = SiteSettings::find(1);
            if($site_settings->settings['data_reset']==0) {
                $schedule->command('vpn:resetdata')->daily();
            } else if($site_settings->settings['data_reset']==1) {
                $schedule->command('vpn:resetdata')->weekly();
            } else if($site_settings->settings['data_reset']==2) {
                $schedule->command('vpn:resetdata')->monthly();
            }
        }
        $dt = \Carbon\Carbon::now()->toDateString() . '_' . \Carbon\Carbon::now()->toTimeString();
        $schedule->command("db:backup --database=mysql --destination=dropbox --destinationPath={$dt} --compression=gzip")->twiceDaily(1,12);
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
