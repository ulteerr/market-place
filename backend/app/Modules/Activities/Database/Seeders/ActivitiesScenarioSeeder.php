<?php

declare(strict_types=1);

namespace Modules\Activities\Database\Seeders;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Seeder;
use Modules\Activities\Database\Factories\ActivityScheduleFactory;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;

final class ActivitiesScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $footballLeaf = Category::query()->where("slug", Transliteration::slug("Футбол"))->first();
        $vocalLeaf = Category::query()->where("slug", Transliteration::slug("Вокал"))->first();

        $footballOrganization = Organization::factory()->create([
            "name" => "Футбольный клуб",
            "status" => "active",
        ]);
        $footballLocation = OrganizationLocation::factory()->create([
            "organization_id" => $footballOrganization->id,
        ]);

        $musicOrganization = Organization::factory()->create([
            "name" => "Музыкальная школа",
            "status" => "active",
        ]);
        $musicLocation = OrganizationLocation::factory()->create([
            "organization_id" => $musicOrganization->id,
        ]);

        $footballActivity = Activity::factory()
            ->published()
            ->forOrganizationLocation($footballOrganization, $footballLocation)
            ->create([
                "name" => "Футбольная секция",
                "slug" => Transliteration::slug("Футбольная секция"),
                "short_description" => "Детская футбольная школа",
                "description" => "Детская футбольная школа",
                "min_age" => 6,
                "max_age" => 12,
                "capacity" => 20,
                "price_from" => 1500,
                "price_to" => 3000,
            ]);

        $musicActivity = Activity::factory()
            ->published()
            ->forOrganizationLocation($musicOrganization, $musicLocation)
            ->create([
                "name" => "Музыкальная студия",
                "slug" => Transliteration::slug("Музыкальная студия"),
                "short_description" => "Занятия музыкой для детей",
                "description" => "Занятия музыкой для детей",
                "min_age" => 5,
                "max_age" => 10,
                "capacity" => 15,
                "price_from" => 2000,
                "price_to" => 4000,
            ]);

        if ($footballLeaf) {
            $footballActivity->primaryCategory()->sync([(string) $footballLeaf->id]);
        }

        if ($vocalLeaf) {
            $musicActivity->primaryCategory()->sync([(string) $vocalLeaf->id]);
        }

        ActivityScheduleFactory::new()
            ->forActivity($footballActivity)
            ->create([
                "day_of_week" => 1,
                "start_time" => "17:00:00",
                "end_time" => "18:30:00",
            ]);
        ActivityScheduleFactory::new()
            ->forActivity($footballActivity)
            ->create([
                "day_of_week" => 3,
                "start_time" => "17:00:00",
                "end_time" => "18:30:00",
            ]);
        ActivityScheduleFactory::new()
            ->forActivity($musicActivity)
            ->create([
                "day_of_week" => 2,
                "start_time" => "16:00:00",
                "end_time" => "17:30:00",
            ]);
        ActivityScheduleFactory::new()
            ->forActivity($musicActivity)
            ->create([
                "day_of_week" => 4,
                "start_time" => "16:00:00",
                "end_time" => "17:30:00",
            ]);
    }
}
