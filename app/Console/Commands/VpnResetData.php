<?php

namespace App\Console\Commands;

use \App\SiteSettings;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VpnResetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpn:resetdata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset data allocation.';

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
            $site_settings = SiteSettings::find(1);
            User::query()->update(['consumable_data' => $site_settings->settings['consumable_data'] * 1048576]);
        }
    }
}
