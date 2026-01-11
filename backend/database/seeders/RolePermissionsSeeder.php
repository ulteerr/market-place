<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // admin получает все
        DB::table('role_permissions')->insert([
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 1, 'permission_id' => 3],
        ]);

        // manager
        DB::table('role_permissions')->insert([
            ['role_id' => 2, 'permission_id' => 1],
            ['role_id' => 2, 'permission_id' => 2],
        ]);

        // staff
        DB::table('role_permissions')->insert([
            ['role_id' => 3, 'permission_id' => 1],
        ]);
    }
}
