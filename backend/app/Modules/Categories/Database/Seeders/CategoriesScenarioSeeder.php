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
        $sports = Category::factory()
            ->root()
            ->create([
                "name" => "Спорт",
                "slug" => "sport",
                "sort_order" => 10,
            ]);

        Category::factory()
            ->count(3)
            ->sequence(
                ["name" => "Футбол", "slug" => "futbol", "sort_order" => 10],
                ["name" => "Волейбол", "slug" => "voleibol", "sort_order" => 20],
                ["name" => "Флорбол", "slug" => "florbol", "sort_order" => 30],
            )
            ->childOf($sports)
            ->create();

        $creative = Category::factory()
            ->root()
            ->create([
                "name" => "Творчество",
                "slug" => "tvorchestvo",
                "sort_order" => 20,
            ]);

        Category::factory()
            ->count(2)
            ->sequence(
                ["name" => "Рисование", "slug" => "risovanie", "sort_order" => 10],
                ["name" => "Музыка", "slug" => "muzyka", "sort_order" => 20],
            )
            ->childOf($creative)
            ->create();

        Category::factory()
            ->root()
            ->inactive()
            ->create([
                "name" => "Архив",
                "slug" => "arkhiv",
                "sort_order" => 999,
            ]);
    }
}
