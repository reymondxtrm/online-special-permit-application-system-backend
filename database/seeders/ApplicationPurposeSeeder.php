<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ApplicationPurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('application_purposes')->insert([
            [
                'special_permit_type_id' => 1,
                'type' =>'permanent',
                'name' => 'Local Employment'
            ],
            [
                'special_permit_type_id' => 1,
                'type' =>'permanent',
                'name' => 'Solemnizing Officer'
            ],
            [
                'special_permit_type_id' => 1,
                'type' =>'permanent',
                'name' => 'Residency'
            ],
            [
                'special_permit_type_id' => 2,
                'type' =>'permanent',
                'name' => 'Local Employment'
            ],
            [
                'special_permit_type_id' => 2,
                'type' =>'permanent',
                'name' => 'PNP Application'
            ],


        ]);
    }
}
