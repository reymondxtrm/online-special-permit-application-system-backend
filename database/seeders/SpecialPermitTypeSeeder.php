<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialPermitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
         DB::table('special_permit_types')->insert([
            [
                'code' => 'mayors_permit',
                'name' => 'Mayors Permit'
            ],
            [
                'code' => 'good_moral',
                'name' => 'Good Moral'
            ],
            [
                'code' => 'event',
                'name' => 'Event'
            ],
            [
                'code' => 'motorcade',
                'name' => 'Motorcade'
            ],
            [
                'code' => 'parade',
                'name' => 'Parade'
            ],
            [
                'code' => 'recorrida',
                'name' => 'Recorrida'
            ],
            [
                'code' => 'use_of_government_property',
                'name' => 'Use of Government Property'
            ],
            [
                'code' => 'occupational_permit',
                'name' => 'Occupational Permit'
            ],

        ]);
    }
}
