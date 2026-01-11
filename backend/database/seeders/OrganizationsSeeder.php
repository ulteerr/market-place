<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('organizations')->insert([
            ['name' => 'Joki Joya', 'description' => 'Детский праздник', 'type' => 'entertainment', 'owner_user_id' => 1, 'status' => 'active'],
            ['name' => 'Mouse House', 'description' => 'Семейный клуб', 'type' => 'club', 'owner_user_id' => 2, 'status' => 'active'],
        ]);

        DB::table('organization_locations')->insert([
            ['organization_id' => 1, 'country_id' => 1, 'region_id' => 1, 'city_id' => 1, 'district_id' => 1, 'address' => 'ул. Детская, 10', 'lat' => 55.75, 'lng' => 37.62],
            ['organization_id' => 2, 'country_id' => 1, 'region_id' => 2, 'city_id' => 2, 'district_id' => 2, 'address' => 'ул. Клубная, 5', 'lat' => 59.93, 'lng' => 30.33],
        ]);
    }
}
