<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // factory(\App\User::class, 1)->create();
        // $this->call(UsersTableSeeder::class);
        DB::table('roles')->insert([
            'name' => 'Unlimited Credits',
            'code' => 'PCODE_001',
            'desc' => 'Unlimited Credits',
        ]);
        DB::table('roles')->insert([
            'name' => 'Subtract Credits',
            'code' => 'PCODE_002',
            'desc' => 'Subtract Credits',
        ]);
        DB::table('roles')->insert([
            'name' => 'Force user package upgrade',
            'code' => 'PCODE_003',
            'desc' => 'Force user package upgrade',
        ]);
        DB::table('roles')->insert([
            'name' => 'Change username',
            'code' => 'PCODE_004',
            'desc' => 'Change username',
        ]);
        DB::table('roles')->insert([
            'name' => 'Update user duration',
            'code' => 'PCODE_005',
            'desc' => 'Update user duration',
        ]);
        DB::table('roles')->insert([
            'name' => 'Create New User',
            'code' => 'PCODE_006',
            'desc' => 'Create New User',
        ]);

        DB::table('statuses')->insert([
            'class' => 'default',
            'name_set' => 'Deactivate',
            'name_get' => 'Deactivated',
        ]);
        DB::table('statuses')->insert([
            'class' => 'primary',
            'name_set' => 'Activate',
            'name_get' => 'Activated',
        ]);
        DB::table('statuses')->insert([
            'class' => 'danger',
            'name_set' => 'Suspend',
            'name_get' => 'Suspended',
        ]);

        DB::table('user_groups')->insert([
            'class' => 'danger',
            'name' => 'Administrator',
        ]);
        DB::table('user_groups')->insert([
            'class' => 'warning',
            'name' => 'Sub-Admin',
        ]);
        DB::table('user_groups')->insert([
            'class' => 'success',
            'name' => 'Reseller',
        ]);
        DB::table('user_groups')->insert([
            'class' => 'primary',
            'name' => 'Sub-Reseller',
        ]);
        DB::table('user_groups')->insert([
            'class' => 'info',
            'name' => 'Client',
        ]);

        DB::table('user_packages')->insert([
            'class' => 'primary',
            'name' => 'Bronze',
            'user_package' => '{"name":"Premium","cost":"1","minimum_duration":"0","min_credit":"1","max_credit":"1","device":"1","data":"512"}'
        ]);
        DB::table('user_packages')->insert([
            'class' => 'success',
            'name' => 'Silver',
            'user_package' => '{"name":"VIP","cost":"2","minimum_duration":"60","min_credit":"2","max_credit":"2","device":"1","data":"1024"}'
        ]);
        DB::table('user_packages')->insert([
            'class' => 'warning',
            'name' => 'Gold',
            'user_package' => '{"name":"Private","cost":"3","minimum_duration":"90","min_credit":"3","max_credit":"3","device":"1","data":"1536"}'
        ]);
        
        DB::table('site_settings')->insert([
            'settings' => '{"domain":"http:\/\/localhost:8080","queue_driver":"database","backup_dir":"domain.com","site_name":"VPN Panel","backup":false,"db_cron":"0 *\/12 * * * *","data_reset":true,"data_reset_cron":"0 * * * * *","trial_period":"2","consumable_data":"512","cf_zone":"1234","enable_panel_login":true,"enable_vpn_login":true,"max_transfer_credits":"100","public_online_users":false,"public_credit_distributors":false,"public_server_status":false}',
        ]);
    }
}
