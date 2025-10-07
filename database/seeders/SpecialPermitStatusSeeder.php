<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SpecialPermitStatusSeeder extends Seeder
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
                'code' => 'pending',
                'name' => 'Pending',
                'description' => null,
            ],
            [
                'code' => 'for_payment',
                'name' => 'For Payment',
                'description' => null,
            ],
            [
                'code' => 'for_signature',
                'name' => 'For Signature',
                'description' => null,
            ],
            [
                'code' => 'completed',
                'name' => 'Completed',
                'description' => null,
            ],
            [
                'code' => 'for_payment_approval',
                'name' => 'For Payment Approval',
                'description' => null,
            ],
        ]);
    }
}
