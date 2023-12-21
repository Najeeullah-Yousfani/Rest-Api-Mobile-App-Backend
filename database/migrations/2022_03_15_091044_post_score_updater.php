<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PostScoreUpdater extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
         //
         $procedure = "
         CREATE FUNCTION `post_score_updater` (fav_id INT, favourite_score float, alt_id INT, alternative_score float)
         RETURNS varchar(255)
         READS SQL DATA
         DETERMINISTIC
         BEGIN
         DECLARE score_return varchar(255);
         DECLARE favourite_score_updated float;
         DECLARE alternative_score_updated float;

         IF favourite_score < alternative_score THEN

               SET favourite_score_updated   = ((alternative_score - favourite_score) / favourite_score);
               SET alternative_score_updated = ((alternative_score - favourite_score) / favourite_score) * (-1);


         ELSEIF favourite_score = alternative_score THEN

               SET favourite_score_updated   = ( 1 / favourite_score);
               SET alternative_score_updated = ( 1 / favourite_score) * (-1);

         ELSEIF favourite_score > alternative_score THEN

               SET favourite_score_updated   = ((alternative_score / favourite_score) / favourite_score);
               SET alternative_score_updated   = ((alternative_score / favourite_score) / favourite_score) * (-1);

         END IF;

             SET score_return = CONCAT(favourite_score_updated,',',alternative_score_updated);
             RETURN(score_return);

         END;

           DELIMITER ;";

         \DB::unprepared("DROP FUNCTION IF EXISTS `post_score_updater`");
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
