<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('regions')->insert([
            ['name' => 'Московская область', 'country_id' => 1],
            ['name' => 'Санкт-Петербург', 'country_id' => 1],
            ['name' => 'Бавария', 'country_id' => 3],
        ]);
    }
}
