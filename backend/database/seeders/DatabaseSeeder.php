<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Users\Database\Seeders\UsersSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountriesSeeder::class,
            RegionsSeeder::class,
            CitiesSeeder::class,
            OrganizationRolesSeeder::class,
            PermissionsSeeder::class,
            RolePermissionsSeeder::class,
            CategoriesSeeder::class,
            UsersSeeder::class,
            ChildrenSeeder::class,
            OrganizationsSeeder::class,
            ActivitiesSeeder::class,
            ActivityCategoriesSeeder::class,
            LeadsSeeder::class,
        ]);
    }
}
