<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationRolesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('organization_roles')->insert([
            ['code' => 'admin', 'name' => 'Администратор'],
            ['code' => 'manager', 'name' => 'Менеджер'],
            ['code' => 'staff', 'name' => 'Сотрудник'],
        ]);
    }
}
