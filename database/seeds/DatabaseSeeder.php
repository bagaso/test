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
            'name' => 'Create Account',
            'code' => 'PCODE_001',
            'desc' => 'Create Account',
        ]);
        DB::table('roles')->insert([
            'name' => 'Update User Account',
            'code' => 'PCODE_002',
            'desc' => 'Update User Account',
        ]);
        DB::table('roles')->insert([
            'name' => 'Update User Password',
            'code' => 'PCODE_003',
            'desc' => 'Update User Password',
        ]);
        DB::table('roles')->insert([
            'name' => 'Set New User Status',
            'code' => 'PCODE_004',
            'desc' => 'Set New User Status',
        ]);
        DB::table('roles')->insert([
            'name' => 'Update User Status',
            'code' => 'PCODE_005',
            'desc' => 'Update User Status',
        ]);
        DB::table('roles')->insert([
            'name' => 'Delete User',
            'code' => 'PCODE_006',
            'desc' => 'Delete User',
        ]);
        DB::table('roles')->insert([
            'name' => 'Manage Other User Account',
            'code' => 'PCODE_007',
            'desc' => 'Manage Other User Account',
        ]);
        DB::table('roles')->insert([
            'name' => 'Manage Other User Password',
            'code' => 'PCODE_008',
            'desc' => 'Manage Other User Password',
        ]);
        DB::table('roles')->insert([
            'name' => 'Manage Other User Status',
            'code' => 'PCODE_009',
            'desc' => 'Manage Other User Status',
        ]);
        DB::table('roles')->insert([
            'name' => 'Delete Other User',
            'code' => 'PCODE_010',
            'desc' => 'Delete Other User',
        ]);
        DB::table('roles')->insert([
            'name' => 'Update User Group',
            'code' => 'PCODE_011',
            'desc' => 'Update User Group',
        ]);
        DB::table('roles')->insert([
            'name' => 'Manage Other User Group',
            'code' => 'PCODE_012',
            'desc' => 'Manage Other User Group',
        ]);
        DB::table('roles')->insert([
            'name' => 'Transfer Credits to User',
            'code' => 'PCODE_013',
            'desc' => 'Transfer Credits to User',
        ]);
        DB::table('roles')->insert([
            'name' => 'Transfer Credits to Other User',
            'code' => 'PCODE_014',
            'desc' => 'Transfer Credits to Other User',
        ]);
        DB::table('roles')->insert([
            'name' => 'Generate Voucher',
            'code' => 'PCODE_015',
            'desc' => 'Generate Voucher',
        ]);
        DB::table('roles')->insert([
            'name' => 'Apply Voucher to User',
            'code' => 'PCODE_016',
            'desc' => 'Apply Voucher to User',
        ]);
        DB::table('roles')->insert([
            'name' => 'Apply Voucher to Other User',
            'code' => 'PCODE_017',
            'desc' => 'Apply Voucher to Other User',
        ]);
        DB::table('roles')->insert([
            'name' => 'User Package',
            'code' => 'PCODE_018',
            'desc' => 'User Package',
        ]);
        DB::table('roles')->insert([
            'name' => 'Other User Package',
            'code' => 'PCODE_019',
            'desc' => 'Other User Package',
        ]);
        
        DB::table('site_settings')->insert([
            'settings' => '{"site_name":"VPN","domain":"https://domain.com","db_cron":"0 */12 * * * *","data_reset_cron":"0 0 * * * *","trial_period":7200,"consumable_data":1024,"cf_zone":"123"}',
        ]);
    }
}
