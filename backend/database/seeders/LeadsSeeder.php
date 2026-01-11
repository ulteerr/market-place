<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('leads')->insert([
            ['activity_id' => 1, 'user_id' => 1, 'child_id' => 1, 'status' => 'new', 'created_at' => now()],
            ['activity_id' => 2, 'user_id' => 2, 'child_id' => 2, 'status' => 'new', 'created_at' => now()],
        ]);
    }
}
