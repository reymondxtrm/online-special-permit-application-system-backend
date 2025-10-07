<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdditionalBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('businesses')->insert([
            ['id' => 14415, 'permit_type_id' => 1, 'gender_type_id' => 2, 'business_code' => null, 'control_no' => '2025618-014390', 'business_permit' => null, 'plate_no' => null, 'with_sticker' => 0, 'name' => 'JANALAN RY GOODS STORE', 'owner' => 'MALACO, JANALAN AMBULO', 'status' => 'assessment_released', 'created_at' => '2025-06-18 10:14:10', 'year' => '2025', 'updated_at' => '2025-06-18 10:14:10']
        ]);
    }
}
