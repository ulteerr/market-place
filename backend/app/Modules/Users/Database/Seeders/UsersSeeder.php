<?php
declare(strict_types=1);

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\User;

final class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email'      => 'parent1@example.com',
                'phone'      => '+79991112233',
                'first_name' => 'Ivan',
                'last_name'  => 'Ivanov',
            ],
            [
                'email'      => 'parent2@example.com',
                'phone'      => '+79994445566',
                'first_name' => 'Maria',
                'last_name'  => 'Petrova',
            ],
        ];

        foreach ($users as $data) {
            User::factory()->create($data);
        }
    }
}