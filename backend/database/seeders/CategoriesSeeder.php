<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Спорт', 'parent_id' => null, 'slug' => 'sport'],
            ['name' => 'Танцы', 'parent_id' => 1, 'slug' => 'dancing'],
            ['name' => 'Музыка', 'parent_id' => null, 'slug' => 'music'],
            ['name' => 'Рисование', 'parent_id' => null, 'slug' => 'drawing'],
        ]);
    }
}
