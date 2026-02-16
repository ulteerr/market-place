<?php

declare(strict_types=1);

namespace Modules\Children\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Children\Models\Child;
use Modules\Users\Models\User;

final class ChildrenSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->select("id")->limit(10)->get();
        $maleFirstNames = [
            "Артем",
            "Илья",
            "Матвей",
            "Михаил",
            "Тимофей",
            "Даниил",
            "Никита",
            "Кирилл",
            "Егор",
            "Андрей",
        ];
        $femaleFirstNames = [
            "Мария",
            "София",
            "Алиса",
            "Полина",
            "Виктория",
            "Анна",
            "Ева",
            "Дарья",
            "Арина",
            "Екатерина",
        ];
        $maleLastNames = [
            "Иванов",
            "Петров",
            "Сидоров",
            "Смирнов",
            "Кузнецов",
            "Попов",
            "Соколов",
            "Морозов",
            "Васильев",
            "Новиков",
        ];
        $femaleLastNames = [
            "Иванова",
            "Петрова",
            "Сидорова",
            "Смирнова",
            "Кузнецова",
            "Попова",
            "Соколова",
            "Морозова",
            "Васильева",
            "Новикова",
        ];
        $maleMiddleNames = [
            "Алексеевич",
            "Ильич",
            "Матвеевич",
            "Михайлович",
            "Тимофеевич",
            "Даниилович",
            "Никитич",
            "Кириллович",
            "Егорович",
            "Андреевич",
        ];
        $femaleMiddleNames = [
            "Алексеевна",
            "Ильинична",
            "Матвеевна",
            "Михайловна",
            "Тимофеевна",
            "Данииловна",
            "Никитична",
            "Кирилловна",
            "Егоровна",
            "Андреевна",
        ];

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            for ($childIndex = 0; $childIndex < 2; $childIndex += 1) {
                $isFemale = random_int(0, 1) === 1;
                $index = random_int(0, count($maleFirstNames) - 1);
                $age = random_int(3, 12);
                $birthDate = now()->subYears($age)->subDays(random_int(0, 364))->format("Y-m-d");

                Child::query()->create([
                    "user_id" => (string) $user->id,
                    "first_name" => $isFemale ? $femaleFirstNames[$index] : $maleFirstNames[$index],
                    "last_name" => $isFemale ? $femaleLastNames[$index] : $maleLastNames[$index],
                    "middle_name" => $isFemale
                        ? $femaleMiddleNames[$index]
                        : $maleMiddleNames[$index],
                    "gender" => $isFemale ? "female" : "male",
                    "birth_date" => $birthDate,
                ]);
            }
        }
    }
}
