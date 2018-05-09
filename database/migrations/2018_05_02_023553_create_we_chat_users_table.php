<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeChatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('we_chat_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('open_id')->unique()->commit('Wechat Unique ID');
            $table->string('nickname',200)->commit('Wechatnickname');
            $table->string('avatarUrl',300)->commit('wechatAvatarUrl');
            $table->tinyInteger('enable')->default(1);
            $table->integer('score')->default(0);
            $table->char('level','2')->default('D');
            $table->tinyInteger('sex')->default(1);
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
//            $table->tinyInteger('is_proxy')->default(0);
            $table->unsignedInteger('proxy_id')->default(0);
            $table->string('code')->nullable();
//            $table->tinyInteger('flag')->default(0);
            $table->tinyInteger('state')->default(1);
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
        Schema::dropIfExists('we_chat_users');
    }
}
