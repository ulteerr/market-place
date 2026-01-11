<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cities')->insert([
            ['name' => 'Москва', 'region_id' => 1, 'lat' => 55.7558, 'lng' => 37.6173, 'country_id' => 1],
            ['name' => 'Санкт-Петербург', 'region_id' => 2, 'lat' => 59.9343, 'lng' => 30.3351, 'country_id' => 1],
            ['name' => 'Мюнхен', 'region_id' => 3, 'lat' => 48.1351, 'lng' => 11.5820, 'country_id' => 3],
        ]);
    }
}
