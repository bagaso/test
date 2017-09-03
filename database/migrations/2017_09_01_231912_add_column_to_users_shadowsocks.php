<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToUsersShadowsocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('vpn_f_login')->default(0);
            $table->string('ss_password')->default('');
            $table->double('ss_data')->default(3145728000);
            $table->boolean('ss_f_login')->default(0);
            $table->timestamp('ss_lastconnectime')->nullable();
            $table->string('ss_method')->default('aes-256-cfb');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('vpn_f_login');
            $table->dropColumn('ss_port');
            $table->dropColumn('ss_password');
            $table->dropColumn('ss_dl');
            $table->dropColumn('ss_up');
            $table->dropColumn('ss_data');
            $table->dropColumn('ss_f_login');
            $table->dropColumn('ss_lastconnectime');
            $table->dropColumn('ss_method');
        });
    }
}
