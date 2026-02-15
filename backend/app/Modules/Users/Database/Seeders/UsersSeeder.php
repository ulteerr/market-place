<?php

declare(strict_types=1);

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Enums\RoleCode;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

final class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $participantRole = Role::query()
            ->where("code", RoleCode::PARTICIPANT->value)
            ->firstOrFail();
        $superAdminRole = Role::query()->where("code", RoleCode::SUPER_ADMIN->value)->firstOrFail();
        $adminRole = Role::query()->where("code", RoleCode::ADMIN->value)->firstOrFail();
        $moderatorRole = Role::query()->where("code", RoleCode::MODERATOR->value)->firstOrFail();

        $superAdminUser = User::query()->firstOrCreate(
            ["email" => "superadmin@example.com"],
            [
                "first_name" => "System",
                "last_name" => "SuperAdmin",
                "phone" => "+79990001122",
                "password" => "password123",
            ],
        );
        $superAdminUser->roles()->syncWithoutDetaching([$participantRole->id, $superAdminRole->id]);

        $adminUser = User::query()->firstOrCreate(
            ["email" => "admin@example.com"],
            [
                "first_name" => "System",
                "last_name" => "Admin",
                "phone" => "+79991112233",
                "password" => "password123",
            ],
        );
        $adminUser->roles()->syncWithoutDetaching([$participantRole->id, $adminRole->id]);

        $moderatorUser = User::query()->firstOrCreate(
            ["email" => "moderator@example.com"],
            [
                "first_name" => "System",
                "last_name" => "Moderator",
                "phone" => "+79994445566",
                "password" => "password123",
            ],
        );
        $moderatorUser->roles()->syncWithoutDetaching([$participantRole->id, $moderatorRole->id]);

        for ($i = 1; $i <= 50; $i++) {
            $user = User::query()->firstOrCreate(
                ["email" => "participant{$i}@example.com"],
                [
                    "first_name" => "Participant{$i}",
                    "last_name" => "User",
                    "phone" => sprintf("+7999000%04d", $i),
                    "password" => "password123",
                ],
            );

            $user->roles()->syncWithoutDetaching([$participantRole->id]);
        }
    }
}
