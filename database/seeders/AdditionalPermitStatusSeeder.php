<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdditionalPermitStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('special_permit_statuses')->insert([
            [
                'id' => 6,
                'code' => 'returned',
                'name' => 'Returned',
                'description' => null,
            ],
            [
                'id' => 7,
                'code' => 'declined',
                'name' => 'Declined',
                'description' => null,
            ],
        ]);
    }
}
