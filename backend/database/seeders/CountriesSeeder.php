<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->insert([
            ['name' => 'Россия', 'code' => 'RU', 'currency' => 'RUB', 'timezone' => 'Europe/Moscow'],
            ['name' => 'США', 'code' => 'US', 'currency' => 'USD', 'timezone' => 'America/New_York'],
            ['name' => 'Германия', 'code' => 'DE', 'currency' => 'EUR', 'timezone' => 'Europe/Berlin'],
        ]);
    }
}
