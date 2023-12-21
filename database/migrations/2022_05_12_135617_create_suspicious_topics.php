<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspiciousTopics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suspicious_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('topic_id')->constrained('default_topics')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('no_of_post_per_day');
            $table->integer('no_of_post_per_week');
            $table->integer('no_of_post_per_month');
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
        Schema::dropIfExists('suspicious_topics');
    }
}
