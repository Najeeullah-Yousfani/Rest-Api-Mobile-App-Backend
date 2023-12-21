<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->bigIncrements('id');
            $table->foreignId('role_id')->constrained('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onUpdate('cascade')->onDelete('cascade');
            $table->string('city',500)->nullable();
            $table->string('username',100)->nullable();
            $table->string('email',100)->unique();
            $table->string('password');
            $table->string('gender',20)->nullable();
            $table->string('profile_image',1000)->nullable();
            $table->string('thumb_image',1000)->nullable();
            $table->string('bio_details',300)->nullable();
            $table->date('dob')->nullable();
            $table->integer('verification_code')->nullable()->length(4);
            $table->timestamp('verify_code_expiry')->nullable();
            $table->string('device_token')->nullable();
            $table->string('platform')->length(8);
            $table->integer('status')->length(1);
            $table->timestamp('last_login')->nullable();
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
        Schema::dropIfExists('users');
    }
}
