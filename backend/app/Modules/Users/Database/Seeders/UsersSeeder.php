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
                "first_name" => "Системный",
                "last_name" => "Суперадмин",
                "middle_name" => "Петрович",
                "gender" => "male",
                "phone" => "+79990001122",
                "password" => "password123",
            ],
        );
        $superAdminUser->roles()->syncWithoutDetaching([$participantRole->id, $superAdminRole->id]);

        $adminUser = User::query()->firstOrCreate(
            ["email" => "admin@example.com"],
            [
                "first_name" => "Системный",
                "last_name" => "Админ",
                "middle_name" => "Иванович",
                "gender" => "male",
                "phone" => "+79991112233",
                "password" => "password123",
            ],
        );
        $adminUser->roles()->syncWithoutDetaching([$participantRole->id, $adminRole->id]);

        $moderatorUser = User::query()->firstOrCreate(
            ["email" => "moderator@example.com"],
            [
                "first_name" => "Системный",
                "last_name" => "Модератор",
                "middle_name" => "Сергеевич",
                "gender" => "male",
                "phone" => "+79994445566",
                "password" => "password123",
            ],
        );
        $moderatorUser->roles()->syncWithoutDetaching([$participantRole->id, $moderatorRole->id]);

        $maleLastNames = [
            "Иванов",
            "Петров",
            "Сидоров",
            "Кузнецов",
            "Смирнов",
            "Васильев",
            "Попов",
            "Соколов",
            "Морозов",
            "Новиков",
        ];
        $femaleLastNames = [
            "Иванова",
            "Петрова",
            "Сидорова",
            "Кузнецова",
            "Смирнова",
            "Васильева",
            "Попова",
            "Соколова",
            "Морозова",
            "Новикова",
        ];
        $maleFirstNames = [
            "Алексей",
            "Дмитрий",
            "Иван",
            "Сергей",
            "Николай",
            "Павел",
            "Андрей",
            "Максим",
            "Егор",
            "Кирилл",
        ];
        $femaleFirstNames = [
            "Анна",
            "Мария",
            "Елена",
            "Ольга",
            "Наталья",
            "Татьяна",
            "Екатерина",
            "Светлана",
            "Ирина",
            "Юлия",
        ];
        $maleMiddleNames = [
            "Алексеевич",
            "Дмитриевич",
            "Иванович",
            "Сергеевич",
            "Николаевич",
            "Павлович",
            "Андреевич",
            "Максимович",
            "Егорович",
            "Кириллович",
        ];
        $femaleMiddleNames = [
            "Алексеевна",
            "Дмитриевна",
            "Ивановна",
            "Сергеевна",
            "Николаевна",
            "Павловна",
            "Андреевна",
            "Максимовна",
            "Егоровна",
            "Кирилловна",
        ];

        for ($i = 1; $i <= 50; $i++) {
            $isFemale = random_int(0, 1) === 1;
            $index = random_int(0, count($maleFirstNames) - 1);
            $user = User::query()->firstOrCreate(
                ["email" => "participant{$i}@example.com"],
                [
                    "first_name" => $isFemale ? $femaleFirstNames[$index] : $maleFirstNames[$index],
                    "last_name" => $isFemale ? $femaleLastNames[$index] : $maleLastNames[$index],
                    "middle_name" => $isFemale
                        ? $femaleMiddleNames[$index]
                        : $maleMiddleNames[$index],
                    "gender" => $isFemale ? "female" : "male",
                    "phone" => sprintf("+7999000%04d", $i),
                    "password" => "password123",
                ],
            );

            $user->roles()->syncWithoutDetaching([$participantRole->id]);
        }
    }
}
