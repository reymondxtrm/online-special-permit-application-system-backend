<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GenderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('gender_types')->insert([
            [
                'gender_id' => 1,
                'type_id' => null,
            ],
            [
                'gender_id' => 2,
                'type_id' => null,
            ],
            [
                'gender_id' => 1,
                'type_id' => 1,
            ],
            [
                'gender_id' => 1,
                'type_id' => 2,
            ],
            [
                'gender_id' => 2,
                'type_id' => 1,
            ],
            [
                'gender_id' => 2,
                'type_id' => 2,
            ],
            [
                'gender_id' => 2,
                'type_id' => 3,
            ],

        ]);
    }
}
