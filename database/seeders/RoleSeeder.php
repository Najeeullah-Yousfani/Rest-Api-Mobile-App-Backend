<?php

namespace Database\Seeders;

use App\config\Constants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('roles')->insert([
            'name'          =>  'user',
            'status'        =>  Constants::USER_STATUS_ACTIVE_INT,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
        DB::table('roles')->insert([
            'name'          =>  'admin',
            'status'        =>  Constants::USER_STATUS_ACTIVE_INT,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
        DB::table('roles')->insert([
            'name'          =>  'sub-admin',
            'status'        =>  Constants::USER_STATUS_ACTIVE_INT,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
    }
}
