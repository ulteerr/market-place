<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Users\Database\Seeders\RolesSeeder;
use Modules\Users\Database\Seeders\UsersSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RolesSeeder::class, UsersSeeder::class]);
    }
}
