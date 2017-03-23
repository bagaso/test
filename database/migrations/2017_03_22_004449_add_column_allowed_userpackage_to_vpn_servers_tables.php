<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnAllowedUserpackageToVpnServersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->json('allowed_userpackage')->nullable();
            $table->tinyInteger('limit_bandwidth')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->dropColumn('allowed_userpackage');
            $table->dropColumn('limit_bandwidth');
        });
    }
}
