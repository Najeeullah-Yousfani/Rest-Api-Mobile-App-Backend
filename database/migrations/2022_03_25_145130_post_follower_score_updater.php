<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PostFollowerScoreUpdater extends Migration
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
        CREATE FUNCTION `post_follower_score_updater` (fav_id INT, favourite_score float, alt_id INT, alternative_score float, is_follower INT, follow_weight float)
        RETURNS varchar(255)
        READS SQL DATA
        DETERMINISTIC
        BEGIN
        DECLARE score_return varchar(255);
        DECLARE favourite_score_updated float;
        DECLARE alternative_score_updated float;
        DECLARE follower_weightage float;

        SET follower_weightage = follow_weight;

        IF is_follower = 1 THEN

              SET favourite_score_updated   = follower_weightage ;
              SET alternative_score_updated = follower_weightage * (-1);

        ELSEIF is_follower = 0 THEN

              SET favourite_score_updated   = 1 ;
              SET alternative_score_updated = 1 * (-1);


        END IF;

            SET score_return = CONCAT(favourite_score_updated,',',alternative_score_updated);
            RETURN(score_return);

        END;

          DELIMITER ;";

        \DB::unprepared("DROP FUNCTION IF EXISTS `post_follower_score_updater`");
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
