<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number');
            $table->string('name');
            $table->string('phone');
            $table->float('price',18,2);
            $table->unsignedInteger('business_id');
            $table->float('brokerage',18,2);
            $table->unsignedInteger('proxy_id')->default(0);
            $table->text('remark')->nullable();
            $table->tinyInteger('state')->default(1);
            $table->tinyInteger('pay')->default(0);
            $table->unsignedInteger('user_id');
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
        Schema::dropIfExists('loans');
    }
}
