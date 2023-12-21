<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestStoredProcedure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "
        CREATE FUNCTION `within_ten` (score INT,favourite_score INT)
        RETURNS INT
        DETERMINISTIC
        READS SQL DATA
        BEGIN
            DECLARE income INT;

            SET income = 0;

            label1: WHILE income <= 3000 DO
            SET income = income + starting_value;
            END WHILE label1;

            RETURN income;
        END;
        DELIMITER ;
        ";

    \DB::unprepared("DROP FUNCTION IF EXISTS `test_procedure`");
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
