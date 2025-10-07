<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CivilStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
         DB::table('civil_statuses')->insert([
            [
                'code' => 'single',
                'name' => 'Single',
                'description' => null,
            ],
            [
                'code' => 'married',
                'name' => 'Married',
                'description' => null,
            ],
            [
                'code' => 'divorced',
                'name' => 'Divorced',
                'description' => null,
            ],
            [
                'code' => 'widowed',
                'name' => 'Widowed',
                'description' => null,
            ],
            [
                'code' => 'separated',
                'name' => 'Separated',
                'description' => null,
            ],
            [
                'code' => 'common_law',
                'name' => 'Common Law',
                'description' => null,
            ],

        ]);
    }
}
