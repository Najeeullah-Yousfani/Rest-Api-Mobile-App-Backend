<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WithinTen extends Migration
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
        CREATE FUNCTION `within_ten` (score float,favourite_score float,within_ten float)
        RETURNS float
        READS SQL DATA
        DETERMINISTIC
        BEGIN
        DECLARE score_return float;
        IF score >= (favourite_score-5) AND score <= (favourite_score+5) THEN
        SET score_return =  within_ten;
        ELSE
        SET score_return = 1;
        END IF;
        RETURN(score_return);
        END;
        DELIMITER ;";

    \DB::unprepared("DROP FUNCTION IF EXISTS `within_ten`");
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
