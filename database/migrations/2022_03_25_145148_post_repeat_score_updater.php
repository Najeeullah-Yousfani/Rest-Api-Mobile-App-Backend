<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PostRepeatScoreUpdater extends Migration
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
        CREATE FUNCTION `post_repeat_score_updater` (fav_id INT, favourite_score float, alt_id INT, alternative_score float,like_count INT, repeat_weightage float, repeat_limitation float)
        RETURNS varchar(255)
        READS SQL DATA
        DETERMINISTIC
        BEGIN
        DECLARE score_return varchar(255);
        DECLARE favourite_score_updated float;
        DECLARE alternative_score_updated float;
        DECLARE repeat_weight float;
        DECLARE repeat_limit float;
        DECLARE division_value float;

        SET repeat_weight = repeat_weightage;
        SET repeat_limit = repeat_limitation;

        IF repeat_weight / (like_count-repeat_limit) > 0  THEN

        SET division_value   =  (repeat_weight / (like_count-repeat_limit));

        ELSE

        SET division_value = 1;

        END IF;

            SET alternative_score_updated =  division_value * (-1);
            SET favourite_score_updated =  division_value;
            SET score_return = CONCAT(favourite_score_updated,',',alternative_score_updated);
            RETURN(score_return);

        END;

          DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `post_repeat_score_updater`");
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
