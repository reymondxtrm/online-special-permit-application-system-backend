<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaragaGeolocationSeeder extends Seeder
{
    public function run()
    {
        // Path to your CSV file
        $filePath = base_path('database/seeders/data/caraga_geolocations.csv');

        // Read the CSV file
        $data = array_map('str_getcsv', file($filePath));

        // Get the header row
        $header = array_shift($data);

        // Insert rows into the database
        foreach ($data as $row) {
            $rowData = array_combine($header, $row);

            DB::table('caraga_geolocations')->insert([
                'region_id' => $this->parseInteger($rowData['region_id']),
                'prov_id' => $this->parseInteger($rowData['prov_id']),
                'mun_city_id' => $this->parseInteger($rowData['mun_city_id']),
                'barangay_id' => $this->parseInteger($rowData['barangay_id']),
                'psgc_id' => $this->cleanString($rowData['psgc_id']),
                'location_desc' => $rowData['location_desc'] ?? null,
                'correspondence_code' => $this->cleanString($rowData['correspondence_code']),
                'geographic_level' => $rowData['geographic_level'] ?? null,
                'old_names' => $rowData['old_names'] ?? null,
                'city_class' => $rowData['city_class'] ?? null,
                'income_classification' => $rowData['income_classification'] ?? null,
                'urban_rural' => $rowData['urban_rural'] ?? null,
            ]);
        }
    }

    private function parseInteger($value)
    {
        // Convert empty strings to null or default to 0
        return is_numeric($value) ? (int)$value : null;
    }

    private function cleanString($value)
    {
        // Ensure the value is a string and remove `.0` if it exists
        return is_numeric($value) ? rtrim(rtrim((string)$value, '0'), '.') : $value;
    }
}
