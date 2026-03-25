<?php

declare(strict_types=1);

namespace Modules\Categories\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Categories\Database\Factories\CategoryFactory;
use Modules\Categories\Models\Category;

final class CategoriesScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $trees = [
            [
                "root" => ["name" => "Спорт", "slug" => "sport", "sort_order" => 10],
                "children" => [
                    ["name" => "Футбол", "slug" => "futbol", "sort_order" => 10],
                    ["name" => "Волейбол", "slug" => "voleibol", "sort_order" => 20],
                    ["name" => "Плавание", "slug" => "plavanie", "sort_order" => 30],
                ],
            ],
            [
                "root" => ["name" => "Музыка", "slug" => "muzyka", "sort_order" => 20],
                "children" => [
                    ["name" => "Вокал", "slug" => "vokal", "sort_order" => 10],
                    ["name" => "Фортепиано", "slug" => "fortepiano", "sort_order" => 20],
                    ["name" => "Скрипка", "slug" => "skripka", "sort_order" => 30],
                ],
            ],
            [
                "root" => ["name" => "Рисование", "slug" => "risovanie", "sort_order" => 30],
                "children" => [
                    ["name" => "Живопись", "slug" => "zhivopis", "sort_order" => 10],
                    [
                        "name" => "Академический рисунок",
                        "slug" => "akademicheskiy-risunok",
                        "sort_order" => 20,
                    ],
                ],
            ],
            [
                "root" => ["name" => "Балет", "slug" => "balet", "sort_order" => 40],
                "children" => [
                    [
                        "name" => "Классический балет",
                        "slug" => "klassicheskiy-balet",
                        "sort_order" => 10,
                    ],
                    [
                        "name" => "Балет для начинающих",
                        "slug" => "balet-dlya-nachinayushchikh",
                        "sort_order" => 20,
                    ],
                ],
            ],
            [
                "root" => ["name" => "Танцы", "slug" => "tancy", "sort_order" => 50],
                "children" => [
                    [
                        "name" => "Современные танцы",
                        "slug" => "sovremennye-tancy",
                        "sort_order" => 10,
                    ],
                    ["name" => "Хип-хоп", "slug" => "hip-hop", "sort_order" => 20],
                ],
            ],
            [
                "root" => ["name" => "Театр", "slug" => "teatr", "sort_order" => 60],
                "children" => [
                    [
                        "name" => "Актерское мастерство",
                        "slug" => "akterskoe-masterstvo",
                        "sort_order" => 10,
                    ],
                    [
                        "name" => "Сценическая речь",
                        "slug" => "scenicheskaya-rech",
                        "sort_order" => 20,
                    ],
                ],
            ],
            [
                "root" => ["name" => "Наука", "slug" => "nauka", "sort_order" => 70],
                "children" => [
                    ["name" => "Эксперименты", "slug" => "eksperimenty", "sort_order" => 10],
                    ["name" => "Астрономия", "slug" => "astronomiya", "sort_order" => 20],
                ],
            ],
            [
                "root" => ["name" => "Языки", "slug" => "yazyki", "sort_order" => 80],
                "children" => [
                    ["name" => "Английский язык", "slug" => "angliyskiy-yazyk", "sort_order" => 10],
                    ["name" => "Китайский язык", "slug" => "kitayskiy-yazyk", "sort_order" => 20],
                ],
            ],
            [
                "root" => ["name" => "Шахматы", "slug" => "shakhmaty", "sort_order" => 90],
                "children" => [
                    [
                        "name" => "Шахматы для начинающих",
                        "slug" => "shakhmaty-dlya-nachinayushchikh",
                        "sort_order" => 10,
                    ],
                    [
                        "name" => "Турнирные шахматы",
                        "slug" => "turnirnye-shakhmaty",
                        "sort_order" => 20,
                    ],
                ],
            ],
            [
                "root" => [
                    "name" => "Робототехника",
                    "slug" => "robototekhnika",
                    "sort_order" => 100,
                ],
                "children" => [
                    ["name" => "LEGO-роботы", "slug" => "lego-roboty", "sort_order" => 10],
                    [
                        "name" => "Программирование роботов",
                        "slug" => "programmirovanie-robotov",
                        "sort_order" => 20,
                    ],
                ],
            ],
        ];

        foreach ($trees as $tree) {
            $root = Category::query()->firstOrCreate(
                ["slug" => $tree["root"]["slug"]],
                [
                    "name" => $tree["root"]["name"],
                    "parent_id" => null,
                    "sort_order" => $tree["root"]["sort_order"],
                    "is_active" => true,
                ],
            );

            /** @var Category $root */

            foreach ($tree["children"] as $child) {
                Category::query()->firstOrCreate(
                    ["slug" => $child["slug"]],
                    [
                        "name" => $child["name"],
                        "parent_id" => (string) $root->getKey(),
                        "sort_order" => $child["sort_order"],
                        "is_active" => true,
                    ],
                );
            }
        }

        Category::query()->firstOrCreate(
            ["slug" => "arkhiv"],
            [
                "name" => "Архив",
                "parent_id" => null,
                "sort_order" => 999,
                "is_active" => false,
            ],
        );
    }
}
