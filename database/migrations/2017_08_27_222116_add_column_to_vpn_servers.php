<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToVpnServers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->string('dl_speed')->default('0kbit');
            $table->string('up_speed')->default('0kbit');
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
            $table->dropColumn('dl_speed');
            $table->dropColumn('up_speed');
        });
    }
}
