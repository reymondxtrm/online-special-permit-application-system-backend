<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // adding Admin Account
        DB::table('users')->insert([
            // 1
            [
                'fname' => 'rizalyn',
                'mname' => 'b',
                'lname' => 'dy',
                'username' => 'rizalyn.dy',
                'password' => Hash::make('password'),
            ],
            // 2
            [
                'fname' => 'cynthia',
                'mname' => 'p',
                'lname' => 'dumigi',
                'username' => 'cynthia.dumigi',
                'password' => Hash::make('password'),
            ],
            // 3
            [
                'fname' => 'ivy joy',
                'mname' => 's',
                'lname' => 'ringel-collado',
                'username' => 'ivyjoy.collado',
                'password' => Hash::make('password'),
            ],
            // 4
            [
                'fname' => 'fionna maraiah',
                'mname' => 'd',
                'lname' => 'catap',
                'username' => 'fionna.catap',
                'password' => Hash::make('password'),
            ],
            // 5
            [
                'fname' => 'kent axl',
                'mname' => '',
                'lname' => 'roa',
                'username' => 'kentaxl.roa',
                'password' => Hash::make('password'),
            ],
            // 6
            [
                'fname' => 'faith marie',
                'mname' => 's',
                'lname' => 'palma',
                'username' => 'faithmarie.palma',
                'password' => Hash::make('password'),
            ],
            // 7
            [
                'fname' => 'raflyn joy',
                'mname' => 'o',
                'lname' => 'monteveros',
                'username' => 'raflynjoy.monteveros',
                'password' => Hash::make('password'),
            ],
            // 8
            [
                'fname' => 'mariaflor',
                'mname' => 'a',
                'lname' => 'dublois',
                'username' => 'mariaflor.dublois',
                'password' => Hash::make('password'),
            ],
            // 9
            [
                'fname' => 'joshua',
                'mname' => 'e',
                'lname' => 'dumantay',
                'username' => 'joshua.dumantay',
                'password' => Hash::make('password'),
            ],
            // 10
            [
                'fname' => 'quennie jane',
                'mname' => 'd',
                'lname' => 'soria',
                'username' => 'quenniejane.soria',
                'password' => Hash::make('password'),
            ],
            // 11
            [
                'fname' => 'admin',
                'mname' => null,
                'lname' => null,
                'username' => 'admin@admin',
                'password' => Hash::make('password'),
            ],
            // 12
            [
                'fname' => 'initial_receiver',
                'mname' => null,
                'lname' => null,
                'username' => 'initial_receiver@user',
                'password' => Hash::make('password'),
            ],
            // 13
            [
                'fname' => 'assessment_receiver',
                'mname' => null,
                'lname' => null,
                'username' => 'assessment_receiver@user',
                'password' => Hash::make('password'),
            ],
            // 14
            [
                'fname' => 'assessment_releaser',
                'mname' => null,
                'lname' => null,
                'username' => 'assessment_releaser@user',
                'password' => Hash::make('password'),
            ],
            // 15
            [
                'fname' => 'complete_receiver',
                'mname' => null,
                'lname' => null,
                'username' => 'complete_receiver@user',
                'password' => Hash::make('password'),
            ],
            // 16
            [
                'fname' => 'final_releaser',
                'mname' => null,
                'lname' => null,
                'username' => 'final_releaser@user',
                'password' => Hash::make('password'),
            ],
            // 17
            [
                'fname' => 'jan myrtle',
                'mname' => null,
                'lname' => 'villanueva',
                'username' => 'janmyrtle.villanueva',
                'password' => Hash::make('password'),
            ],
            // 18
            [
                'fname' => 'tiffany mae',
                'mname' => null,
                'lname' => 'adane',
                'username' => 'tiffanymae.adane',
                'password' => Hash::make('password'),
            ],
            // 19
            [
                'fname' => 'dianne kristine',
                'mname' => null,
                'lname' => 'brodeth',
                'username' => 'diannekristine.brodeth',
                'password' => Hash::make('password'),
            ],
            // 20
            [
                'fname' => 'karl gabriel',
                'mname' => null,
                'lname' => 'cultura',
                'username' => 'karlgabriel.cultura',
                'password' => Hash::make('password'),
            ],
        ]);
    }
}
