<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOnlineUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_users', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->string('user_ip')->default('0.0.0.0');
            $table->string('user_port')->default('0');
            $table->integer('vpn_server_id')->unsigned();
            $table->double('byte_sent')->default(0)->unsigned();
            $table->double('byte_received')->default(0)->unsigned();
            $table->double('data_available')->default(0)->unsigned();
            $table->timestamps();
            $table->index(['user_id', 'vpn_server_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('online_users');
    }
}
