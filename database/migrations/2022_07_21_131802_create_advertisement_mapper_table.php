<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisementMapperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertisement_mapper', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('custom_add_id')->constrained('custom_ads')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('other_custom_add_id')->nullable()->constrained('custom_ads')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('type')->length(1);
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
        Schema::dropIfExists('advertisement_mapper');
    }
}
