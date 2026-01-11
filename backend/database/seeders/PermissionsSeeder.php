<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['code' => 'view_activities', 'description' => 'Просмотр всех активностей'],
            ['code' => 'edit_activities', 'description' => 'Редактирование активностей'],
            ['code' => 'manage_users', 'description' => 'Управление пользователями'],
        ]);
    }
}
