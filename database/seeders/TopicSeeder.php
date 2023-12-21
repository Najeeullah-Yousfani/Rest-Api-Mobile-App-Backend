<?php

namespace Database\Seeders;

use App\config\Constants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('default_topics')->insert([
            'name'          =>  'Adverts',
            'status'        =>  Constants::USER_STATUS_ACTIVE,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
        DB::table('default_topics')->insert([
            'name'          =>  'Animals',
            'status'        =>  Constants::USER_STATUS_ACTIVE,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
        DB::table('default_topics')->insert([
            'name'          =>  'Art',
            'status'        =>  Constants::USER_STATUS_ACTIVE,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
        DB::table('default_topics')->insert([
            'name'          =>  'Beauty',
            'status'        =>  Constants::USER_STATUS_ACTIVE,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
    }
}
