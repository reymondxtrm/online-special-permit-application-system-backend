<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $this->call(AdditionalRoleSeeder::class);
        $this->call(AddtionalSuperAdminUser::class);
        $this->call(AddtionalSuperAdminUserRoleSeeder::class);
    }
}
