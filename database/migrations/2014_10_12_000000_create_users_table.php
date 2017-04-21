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
            $table->string('password');
            $table->string('email')->unique();
            $table->string('fullname');
            $table->string('contact')->nullable();
            $table->integer('credits')->default(0)->unsigned();
            $table->integer('vpn_session')->default(1)->unsigned();
            $table->integer('user_package_id')->default(1)->unsigned();
            $table->double('lifetime_bandwidth')->default(0)->unsigned();
            $table->double('consumable_data')->unsigned()->default(0);
            $table->integer('user_group_id')->unsigned()->default(5);
            $table->integer('distributor')->unsigned()->default(0);
            $table->integer('status_id')->unsigned();
            $table->integer('parent_id')->default(1);
            $table->timestamp('expired_at')->useCurrent();
            $table->timestamp('pause_start')->nullable();
            $table->timestamp('pause_end')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_group_id', 'status_id', 'parent_id', 'expired_at']);
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
