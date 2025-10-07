<?php

namespace Database\Seeders;

use App\Models\ReferenceCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class add_initial_reference_number_to_reference_codes_table extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = ['good_moral', 'mayors_permit', 'event', 'motorcade', 'parade', 'recorrida', 'use_of_government_property', 'occupational_permit'];

        foreach ($types as $type) {
            ReferenceCode::firstOrCreate(
                ['permit_type' => $type], // search condition
                ['current_reference_code' => 0]        // default values
            );
        }
    }
}
