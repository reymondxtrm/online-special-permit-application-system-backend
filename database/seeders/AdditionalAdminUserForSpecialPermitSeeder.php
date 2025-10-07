<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdditionalAdminUserForSpecialPermitSeeder extends Seeder
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
            [
                'id' => '41',
                'fname' => 'special_permit_admin',
                'mname' => null,
                'lname' => null,
                'username' => 'specialPermit@admin',
                'password' => Hash::make('password'),
            ],
            [
                'id' => '42',
                'fname' => 'Ria Blanzche',
                'mname' => 'Laparan',
                'lname' => 'Petallar',
                'username' => 'ria.petallar',
                'password' => Hash::make('password'),
            ],
            // [
            //     'id' => '26',
            //     'fname' => 'special_permit_client',
            //     'mname' => null,
            //     'lname' => null,
            //     'username' => 'specialPermit@client',
            //     'password' => Hash::make('password'),
            // ],
        ]);

    }
}
