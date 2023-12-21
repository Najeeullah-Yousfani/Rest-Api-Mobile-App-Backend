<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GetAgeNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $procedure = "
        CREATE FUNCTION `get_age_new` (age FLOAT,age_new_config float, post_less_days float)
        RETURNS FLOAT
        BEGIN

        DECLARE age_new float;

        IF age <= age_new_config THEN
            SET age_new = post_less_days;

        ELSE
            SET age_new = 1;

        END IF;

        RETURN age_new;

        END;
        DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `get_age_new`");
        \DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
