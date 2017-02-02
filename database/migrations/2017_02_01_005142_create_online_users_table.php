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
            $table->integer('user_id');
            $table->ipAddress('server_ip');
            $table->double('byte_sent')->nullable();
            $table->double('byte_received')->nullable();
            $table->timestamps();
            $table->primary('user_id');
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
