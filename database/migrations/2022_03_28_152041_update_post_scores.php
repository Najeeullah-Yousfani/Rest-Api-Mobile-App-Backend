<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePostScores extends Migration
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
        CREATE FUNCTION `update_post_score` (fav_id INT, favourite_score float, alt_id INT, alternative_score float,like_score_cal float,dislike_score_cal float,alt_age INT,new_days_weight float)
        RETURNS varchar(255)
        READS SQL DATA
        DETERMINISTIC
        BEGIN
        DECLARE score_return INT;
        DECLARE favourite_score_updated float;
        DECLARE alternative_score_updated float;
        DECLARE check_new_days float;
        DECLARE new_days float;

        SET new_days    =   new_days_weight;


        IF alt_age<=7  THEN

            SET check_new_days = new_days;

        ELSE

            SET check_new_days   =  1;

        END IF;

            SET alternative_score_updated =  alternative_score + like_score_cal * (-1) * check_new_days;
            SET favourite_score_updated =  favourite_score + like_score_cal;
            RETURN( CONCAT(alternative_score_updated,',',favourite_score_updated) );

        END;

          DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `update_post_score`");
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
