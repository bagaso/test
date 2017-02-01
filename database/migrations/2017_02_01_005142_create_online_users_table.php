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
            $table->string('username');
            $table->string('server');
            $table->string('byte_sent');
            $table->string('byte_received');
            $table->timestamps();
            //$table->timestamp('time_update')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            //$table->timestamp('created_at')->useCurrent();
            $table->integer('counter');
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
