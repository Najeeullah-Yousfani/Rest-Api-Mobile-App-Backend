<?php

namespace Database\Seeders;

use App\config\Constants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\XmlConfiguration\Constant;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('users')->insert([
            'role_id'   =>  '2',
            'username'  =>  'Admin_FindUr-App',
            'email'     =>  'admin_findUr@yopmail.com',
            'password'  =>  bcrypt('adminasd123$A'),
            'platform'  =>  'ios',
            'status'    =>  Constants::USER_STATUS_ACTIVE_INT,
            'created_at'    =>  '2022-02-11 04:10:21',
            'updated_at'    =>  '2022-02-11 04:10:21',
        ]);
    }
}
