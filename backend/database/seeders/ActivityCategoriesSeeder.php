<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('activity_categories')->insert([
            ['activity_id' => 1, 'category_id' => 1], // Футбол → Спорт
            ['activity_id' => 2, 'category_id' => 3], // Музыка → Музыка
        ]);
    }
}
