<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url',1000)->nullable();
            $table->string('file_url',1000)->nullable();
            $table->string('thumb_url',1000)->nullable();
            $table->integer('media_type')->length(1);
            $table->integer('clicks')->default(0);
            $table->integer('action')->length(1);
            $table->integer('status')->length(1);
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
        Schema::dropIfExists('custom_ads');
    }
}
