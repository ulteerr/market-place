<?php

namespace Database\Seeders;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $sportId = "11111111-1111-1111-1111-111111111111";
        $footballId = "11111111-1111-1111-1111-111111111112";
        $musicId = "11111111-1111-1111-1111-111111111113";
        $vocalId = "11111111-1111-1111-1111-111111111114";
        $drawingId = "11111111-1111-1111-1111-111111111115";

        DB::table("categories")->upsert(
            [
                [
                    "id" => $sportId,
                    "name" => "Спорт",
                    "slug" => Transliteration::slug("Спорт"),
                    "parent_id" => null,
                    "sort_order" => 10,
                    "is_active" => true,
                    "created_at" => $now,
                    "updated_at" => $now,
                ],
                [
                    "id" => $footballId,
                    "name" => "Футбол",
                    "slug" => Transliteration::slug("Футбол"),
                    "parent_id" => $sportId,
                    "sort_order" => 10,
                    "is_active" => true,
                    "created_at" => $now,
                    "updated_at" => $now,
                ],
                [
                    "id" => $musicId,
                    "name" => "Музыка",
                    "slug" => Transliteration::slug("Музыка"),
                    "parent_id" => null,
                    "sort_order" => 20,
                    "is_active" => true,
                    "created_at" => $now,
                    "updated_at" => $now,
                ],
                [
                    "id" => $vocalId,
                    "name" => "Вокал",
                    "slug" => Transliteration::slug("Вокал"),
                    "parent_id" => $musicId,
                    "sort_order" => 10,
                    "is_active" => true,
                    "created_at" => $now,
                    "updated_at" => $now,
                ],
                [
                    "id" => $drawingId,
                    "name" => "Рисование",
                    "slug" => Transliteration::slug("Рисование"),
                    "parent_id" => null,
                    "sort_order" => 30,
                    "is_active" => true,
                    "created_at" => $now,
                    "updated_at" => $now,
                ],
            ],
            ["id"],
            ["name", "slug", "parent_id", "sort_order", "is_active", "updated_at"],
        );
    }
}
