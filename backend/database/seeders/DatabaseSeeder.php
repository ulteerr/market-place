<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Children\Database\Seeders\ChildrenSeeder;
use Modules\Organizations\Database\Seeders\OrganizationsSeeder;
use Modules\Users\Database\Seeders\RolesSeeder;
use Modules\Users\Database\Seeders\UsersSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            ChildrenSeeder::class,
            OrganizationsSeeder::class,
        ]);
    }
}
