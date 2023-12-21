<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PostAgeScoreUpdater extends Migration
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
        CREATE FUNCTION `post_age_score_updater` (fav_id INT, favourite_score float, alt_id INT, alternative_score float,post_like_age INT, post_dislike_age INT,age_post_days_old INT,age_post_days_new INT,age_gt_seven float,age_gt_nine float,age_gt_hund float,new_age float)
        RETURNS varchar(255)
        READS SQL DATA
        DETERMINISTIC
        BEGIN
        DECLARE score_return varchar(255);
        DECLARE favourite_score_updated float;
        DECLARE alternative_score_updated float;
        DECLARE division_value float;
        DECLARE age_y float;
        DECLARE age_x float;
        DECLARE age_g float;
        DECLARE retention_period float;
        DECLARE new_post_days float;
        DECLARE new_y_days float;
        DECLARE data float;




        SET age_y = age_gt_seven;
        SET age_x = age_gt_nine;
        SET age_g = age_gt_hund;
        SET retention_period = age_post_days_old;
        SET new_post_days = age_post_days_new;
        SET new_y_days = new_age;




        IF post_like_age < retention_period  THEN

                SET division_value = age_y;

        ELSEIF post_like_age >= retention_period AND post_like_age<=100 THEN

                SET division_value = age_X;

        ELSE

                SET division_value   =  age_g;

        END IF;

        IF post_dislike_age <= new_post_days  THEN

            SET data = new_y_days;

        ELSE

            SET data   =  1;

        END IF;


            SET alternative_score_updated =  (division_value * (-1)) * data;
            SET favourite_score_updated =  division_value;
            SET score_return = CONCAT(favourite_score_updated,',',alternative_score_updated);
            RETURN(score_return);

        END;

          DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `post_age_score_updater`");
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
