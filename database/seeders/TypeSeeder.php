<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('types')->insert([
            [
                'name' => 'pwd',
                'description' => 'Person with Disability'
            ],
            [
                'name' => 'senior_citizen',
                'description' => 'Senior Citizen'
            ],
            [
                'name' => 'pregnant',
                'description' => 'Pregnant'
            ]

        ]);
    }
}
