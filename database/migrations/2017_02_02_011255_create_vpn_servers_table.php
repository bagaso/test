<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVpnServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vpn_servers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('server_key')->unique();
            $table->ipAddress('server_ip')->unique();
            $table->string('server_port');
            $table->string('vpn_secret');
            $table->string('server_domain')->unique();
            $table->string('server_name')->unique();
            $table->tinyInteger('is_active')->unsigned();
            $table->tinyInteger('free_user')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vpn_servers');
    }
}
