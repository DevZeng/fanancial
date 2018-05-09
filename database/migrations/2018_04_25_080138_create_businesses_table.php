<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
//            $table->string('price');
            $table->float('min',18,2);
            $table->float('max',18,2);
            $table->string('promotion');
            $table->integer('sort')->default(1);
            $table->float('brokerage',18,2)->default(0);
            $table->text('intro')->nullable();
            $table->tinyInteger('state')->default(1);
            $table->tinyInteger('finish')->default(0);
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
        Schema::dropIfExists('businesses');
    }
}
