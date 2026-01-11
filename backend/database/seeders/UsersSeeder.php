<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'email' => 'parent1@example.com',
                'phone' => '+79991112233',
                'password_hash' => Hash::make('password123'),
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'email' => 'parent2@example.com',
                'phone' => '+79994445566',
                'password_hash' => Hash::make('password123'),
                'is_active' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('parent_profiles')->insert([
            ['user_id' => 1, 'first_name' => 'Иван', 'last_name' => 'Иванов'],
            ['user_id' => 2, 'first_name' => 'Мария', 'last_name' => 'Петрова'],
        ]);
    }
}
