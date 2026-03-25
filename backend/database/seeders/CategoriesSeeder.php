<?php

namespace Database\Seeders;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Seeder;
use Modules\Categories\Models\Category;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            ["id" => "11111111-1111-1111-1111-111111111101", "name" => "Спорт", "sort_order" => 10],
            [
                "id" => "11111111-1111-1111-1111-111111111102",
                "name" => "Музыка",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111103",
                "name" => "Рисование",
                "sort_order" => 30,
            ],
            ["id" => "11111111-1111-1111-1111-111111111104", "name" => "Балет", "sort_order" => 40],
            ["id" => "11111111-1111-1111-1111-111111111105", "name" => "Танцы", "sort_order" => 50],
            ["id" => "11111111-1111-1111-1111-111111111106", "name" => "Театр", "sort_order" => 60],
            ["id" => "11111111-1111-1111-1111-111111111107", "name" => "Наука", "sort_order" => 70],
            ["id" => "11111111-1111-1111-1111-111111111108", "name" => "Языки", "sort_order" => 80],
            [
                "id" => "11111111-1111-1111-1111-111111111109",
                "name" => "Шахматы",
                "sort_order" => 90,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111110",
                "name" => "Робототехника",
                "sort_order" => 100,
            ],
        ];

        $leaves = [
            [
                "id" => "11111111-1111-1111-1111-111111111201",
                "name" => "Футбол",
                "parent" => "Спорт",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111202",
                "name" => "Волейбол",
                "parent" => "Спорт",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111203",
                "name" => "Плавание",
                "parent" => "Спорт",
                "sort_order" => 30,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111204",
                "name" => "Вокал",
                "parent" => "Музыка",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111205",
                "name" => "Фортепиано",
                "parent" => "Музыка",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111206",
                "name" => "Скрипка",
                "parent" => "Музыка",
                "sort_order" => 30,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111207",
                "name" => "Живопись",
                "parent" => "Рисование",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111208",
                "name" => "Академический рисунок",
                "parent" => "Рисование",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111209",
                "name" => "Классический балет",
                "parent" => "Балет",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111210",
                "name" => "Балет для начинающих",
                "parent" => "Балет",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111211",
                "name" => "Современные танцы",
                "parent" => "Танцы",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111212",
                "name" => "Хип-хоп",
                "parent" => "Танцы",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111213",
                "name" => "Актерское мастерство",
                "parent" => "Театр",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111214",
                "name" => "Сценическая речь",
                "parent" => "Театр",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111215",
                "name" => "Эксперименты",
                "parent" => "Наука",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111216",
                "name" => "Астрономия",
                "parent" => "Наука",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111217",
                "name" => "Английский язык",
                "parent" => "Языки",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111218",
                "name" => "Китайский язык",
                "parent" => "Языки",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111219",
                "name" => "Шахматы для начинающих",
                "parent" => "Шахматы",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111220",
                "name" => "Турнирные шахматы",
                "parent" => "Шахматы",
                "sort_order" => 20,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111221",
                "name" => "LEGO-роботы",
                "parent" => "Робототехника",
                "sort_order" => 10,
            ],
            [
                "id" => "11111111-1111-1111-1111-111111111222",
                "name" => "Программирование роботов",
                "parent" => "Робототехника",
                "sort_order" => 20,
            ],
        ];

        $rootIdsByName = [];
        foreach ($roots as $root) {
            $rootCategory = Category::query()->firstOrNew([
                "slug" => Transliteration::slug($root["name"]),
            ]);

            if (!$rootCategory->exists) {
                $rootCategory->id = $root["id"];
            }

            $rootCategory->name = $root["name"];
            $rootCategory->parent_id = null;
            $rootCategory->sort_order = $root["sort_order"];
            $rootCategory->is_active = true;
            $rootCategory->save();

            $rootIdsByName[$root["name"]] = (string) $rootCategory->getKey();
        }

        foreach ($leaves as $leaf) {
            $leafCategory = Category::query()->firstOrNew([
                "slug" => Transliteration::slug($leaf["name"]),
            ]);

            if (!$leafCategory->exists) {
                $leafCategory->id = $leaf["id"];
            }

            $leafCategory->name = $leaf["name"];
            $leafCategory->parent_id = $rootIdsByName[$leaf["parent"]];
            $leafCategory->sort_order = $leaf["sort_order"];
            $leafCategory->is_active = true;
            $leafCategory->save();
        }
    }
}
