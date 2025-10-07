<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdditionalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // adding Admin Account
        DB::table('users')->insert([
            // 21
            [
                'fname' => 'jose antonio sean',
                'mname' => 'b',
                'lname' => 'bayotas',
                'username' => 'joseantoniosean.bayotas',
                'password' => Hash::make('password'),
            ],
        ]);
    }
}
