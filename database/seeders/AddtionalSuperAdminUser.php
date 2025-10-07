<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddtionalSuperAdminUser extends Seeder
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
                'fname' => 'super_admin',
                'mname' => null,
                'lname' => null,
                'username' => 'super@admin',
                'password' => Hash::make('password'),
            ],
        ]);
    }
}
