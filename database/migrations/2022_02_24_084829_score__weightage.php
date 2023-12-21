<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScoreWeightage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "
        CREATE FUNCTION `Score_Weightage` (score float)
        RETURNS DECIMAL
        BEGIN
        DECLARE score_return decimal;
        IF score <= 10 THEN
        SET score_return =  2;
        ELSEIF score > 10 AND score <= 20 THEN
        SET score_return = 0.3;
        ELSEIF score > 20 AND score <= 30 THEN
        SET score_return = 0.5;
        ELSEIF score > 30 AND score <= 40 THEN
        SET score_return = 0.7;
        ELSEIF score > 40 AND score <= 50 THEN
        SET score_return = 0.8;
        END IF;
        RETURN(score_return);
        END;
        DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `Score_Weightage`");
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
