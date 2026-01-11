<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChildrenSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('children')->insert([
            ['user_id' => 1, 'name' => 'Алексей', 'birth_date' => '2015-04-12', 'gender' => 'male'],
            ['user_id' => 2, 'name' => 'Екатерина', 'birth_date' => '2013-09-23', 'gender' => 'female'],
        ]);
    }
}
