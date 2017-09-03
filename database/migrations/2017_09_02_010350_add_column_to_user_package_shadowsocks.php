<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToUserPackageShadowsocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->boolean('vpn_login')->default(1);
            $table->boolean('ss_login')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn('vpn_login');
            $table->dropColumn('ss_login');
        });
    }
}
