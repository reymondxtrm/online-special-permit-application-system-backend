<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialPermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $this->call(AdditionalAdminUserForSpecialPermitSeeder::class);
        $this->call(AdditionalSpecialPermitRoleSeeder::class);
        // $this->call(AddtionalRoleSpecialPermitSeeder::class);
        $this->call(AddtionalUserRoleForSpecialPermitSeeder::class);
        $this->call(SpecialPermitTypeSeeder::class);
        $this->call(ApplicationPurposeSeeder::class);
        $this->call(CaragaGeolocationSeeder::class);
        $this->call(CivilStatusSeeder::class);
        $this->call(SpecialPermitStatusSeeder::class);
    }
}
