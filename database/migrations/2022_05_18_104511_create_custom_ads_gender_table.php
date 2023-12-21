<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomAdsGenderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_ads_gender', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('custom_ads_id')->constrained('custom_ads')->onUpdate('cascade')->onDelete('cascade');
            $table->string('gender',30);
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
        Schema::dropIfExists('custom_ads_gender');
    }
}
