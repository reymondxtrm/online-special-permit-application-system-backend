<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrimaryIdType extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('id_types')->insert([
        ['name' => 'Philippine Passport from Department of Foreign Affairs'],
        ['name' => 'National ID'],
        ['name' => 'SSS ID or SSS UMID Card'],
        ['name' => 'GSIS ID or GSIS UMID Card'],
        ['name' => "Driver's License"],
        ['name' => 'PRC ID(Proffesional Regulatory Commission)'],
        ['name' => 'OWWA ID(Overseas Workers Welfare Administration)'],
        ['name' => 'iDOLE ID(Department of Labor and Employement)'],
        ['name' => "Voter's Certification with Dry Seal"],
        ['name' => "Voter's ID(Commission of Elections)"],
        ['name' => 'Firearms License(Philippine National Police)'],
        ['name' => 'Senior Citizen ID(Local Government Unit)'],
        ['name' => 'Person with Disabilities(PWD) ID'],
        ['name' => 'NBI Clearance(National Bureo of Investigation'],
        ['name' => 'Alien Certificate of Registration or Immigrant Certificate of Registration'],
        ['name' => 'Philhealth ID(digitized PVC)'],
        ['name' => 'Government  Office and GOCC ID'],
        ['name' => 'Integrated Bar of the Philippines ID'],
        ['name' => 'School ID(for students) from the current School or University'],
        ['name' => 'Current Valid ePassport(For Renewal of ePassport)'],
        ['name' => "For applicants based overseas they may use their host government issued ID's showing their Philippine citizenship.(Example: Residence Card)"],
    ]);
    }
}
