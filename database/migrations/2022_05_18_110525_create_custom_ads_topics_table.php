<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomAdsTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_ads_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('custom_ads_id')->constrained('custom_ads')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('def_topic_id')->constrained('default_topics')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('custom_ads_topics');
    }
}
