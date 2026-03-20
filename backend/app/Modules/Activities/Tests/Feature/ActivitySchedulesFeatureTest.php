<?php

declare(strict_types=1);

namespace Modules\Activities\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivitySchedulesFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_create_persists_schedules_and_public_show_returns_them_sorted(): void
    {
        $auth = $this->actingAsAdmin();
        [$organization, $location, , $leaf] = $this->createCatalogContext();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/activities", [
                "organization_id" => (string) $organization->id,
                "location_id" => (string) $location->id,
                "category_id" => (string) $leaf->id,
                "name" => "Расписание футбола",
                "slug" => "raspisanie-futbola",
                "short_description" => "Проверка расписания.",
                "status" => "published",
                "schedules" => [
                    [
                        "day_of_week" => 5,
                        "start_time" => "18:00:00",
                        "end_time" => "19:00:00",
                    ],
                    [
                        "day_of_week" => 2,
                        "start_time" => "10:00:00",
                        "end_time" => "11:00:00",
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(2, "data.schedules");

        $activity = Activity::query()->where("slug", "raspisanie-futbola")->firstOrFail();

        $this->assertDatabaseCount("activity_schedules", 2);

        $this->getJson("/api/activities/" . $activity->slug . "-" . $activity->id)
            ->assertOk()
            ->assertJsonPath("data.schedules.0.day_of_week", 2)
            ->assertJsonPath("data.schedules.0.start_time", "10:00:00")
            ->assertJsonPath("data.schedules.1.day_of_week", 5);
    }

    #[Test]
    public function admin_update_replaces_existing_schedule_rows(): void
    {
        $auth = $this->actingAsAdmin();
        [$organization, $location, , $leaf] = $this->createCatalogContext();

        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Секция",
                "slug" => "sekciya",
                "short_description" => "Проверка замены.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        $activity->schedules()->createMany([
            [
                "day_of_week" => 2,
                "start_time" => "10:00:00",
                "end_time" => "11:00:00",
            ],
            [
                "day_of_week" => 4,
                "start_time" => "10:00:00",
                "end_time" => "11:00:00",
            ],
        ]);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/activities/" . $activity->id, [
                "schedules" => [
                    [
                        "day_of_week" => 6,
                        "start_time" => "12:00:00",
                        "end_time" => "13:30:00",
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, "data.schedules")
            ->assertJsonPath("data.schedules.0.day_of_week", 6);

        $this->assertDatabaseCount("activity_schedules", 1);
        $this->assertDatabaseHas("activity_schedules", [
            "activity_id" => (string) $activity->id,
            "day_of_week" => 6,
            "start_time" => "12:00:00",
            "end_time" => "13:30:00",
        ]);
    }

    private function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }

    private function createCatalogContext(): array
    {
        $organization = Organization::factory()->create([
            "name" => "Организация спорта",
            "status" => "active",
        ]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
        ]);
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        return [$organization, $location, $root, $leaf];
    }
}
