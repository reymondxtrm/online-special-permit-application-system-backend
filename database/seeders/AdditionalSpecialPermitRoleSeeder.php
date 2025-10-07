<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdditionalSpecialPermitRoleSeeder extends Seeder
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

            [
                'id' => '7',
                'name' => 'special_permit_admin',
                'description' => 'Special Permit Admin'
            ],
            [
                'id' => '8',
                'name' => 'special_permit_user',
                'description' => 'Special Permit User'
            ],



        ]);
    }
}
