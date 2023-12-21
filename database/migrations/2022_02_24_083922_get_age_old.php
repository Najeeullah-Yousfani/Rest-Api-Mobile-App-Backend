<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GetAgeOld extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "
        CREATE FUNCTION `get_age_old` (age FLOAT,threshold_days float, post_less_days float)
        RETURNS FLOAT
        BEGIN

        DECLARE age_old float;

        IF age >= threshold_days THEN
            SET age_old = post_less_days;

        ELSE
            SET age_old = 1;

        END IF;

        RETURN age_old;

        END;
        DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `get_age_old`");
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
