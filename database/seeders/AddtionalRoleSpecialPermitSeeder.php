<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddtionalRoleSpecialPermitSeeder extends Seeder
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
                'name' => 'special_permit_admin',
                'description' => 'Special Permit Admin'
            ],
            // [
            //     'name' => 'special_permit_client',
            //     'description' => 'Special Permit Client'
            // ],

        ]);
    }
}
