<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddtionalUserRoleForSpecialPermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        DB::table('user_roles')->insert([
            [
                'user_id' => 41,
                'role_id' => 7
            ],
            [
                'user_id' => 42,
                'role_id' => 7
            ],
            // [
            //     'user_id' => 26,
            //     'role_id' => 8
            // ],

        ]);
    }
}
