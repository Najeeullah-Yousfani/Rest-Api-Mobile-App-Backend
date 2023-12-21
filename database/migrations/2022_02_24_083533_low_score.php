<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LowScore extends Migration
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
        CREATE FUNCTION `low_score` (score FLOAT,less_equalfifty float, less_equalforty float, less_equalthirty float, less_equaltwnty float)
        RETURNS FLOAT
        BEGIN

        DECLARE score_return float;

            IF score >= 50 THEN
                SET score_return = 1;

            ELSEIF score<=20 THEN
                SET score_return = less_equaltwnty;

            ELSEIF score>20 AND score<=30 THEN
                SET score_return = less_equalthirty;

            ELSEIF score>30 AND score<=40 THEN
                SET score_return = less_equalforty;

            ELSE
                SET score_return = less_equalfifty;

            END IF;

        RETURN score_return;

        END;
        DELIMITER ;";

    \DB::unprepared("DROP FUNCTION IF EXISTS `low_score`");
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
