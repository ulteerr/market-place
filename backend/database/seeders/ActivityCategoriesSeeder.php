<?php

namespace Database\Seeders;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $activities = DB::table("activities")
            ->select("id", "name")
            ->whereIn("name", ["Футбольная секция", "Музыкальная студия"])
            ->get()
            ->keyBy("name");

        $categories = DB::table("categories")
            ->select("id", "slug")
            ->whereIn("slug", [Transliteration::slug("Футбол"), Transliteration::slug("Вокал")])
            ->get()
            ->keyBy("slug");

        $pairs = [
            ["activity" => "Футбольная секция", "category" => Transliteration::slug("Футбол")],
            ["activity" => "Музыкальная студия", "category" => Transliteration::slug("Вокал")],
        ];

        $rows = [];

        foreach ($pairs as $pair) {
            $activity = $activities->get($pair["activity"]);
            $category = $categories->get($pair["category"]);

            if (!$activity || !$category) {
                continue;
            }

            $rows[] = [
                "activity_id" => (string) $activity->id,
                "category_id" => (string) $category->id,
            ];
        }

        if ($rows === []) {
            return;
        }

        DB::table("activity_categories")->upsert($rows, ["activity_id", "category_id"]);
    }
}
