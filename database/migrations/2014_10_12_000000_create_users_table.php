<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('fullname');
            $table->string('password');
            $table->integer('credits')->default(0)->unsigned();
            $table->integer('user_group_id')->unsigned();
            $table->integer('status_id')->unsigned();
            $table->integer('parent_id')->default(1);
            $table->dateTime('expired_at');
            $table->rememberToken();
            $table->timestamps();
            $table->index('user_group_id');
            $table->index('status_id');
            $table->index('parent_id');
            $table->index('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}