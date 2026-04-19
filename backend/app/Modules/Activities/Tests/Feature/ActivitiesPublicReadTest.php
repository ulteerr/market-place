<?php

declare(strict_types=1);

namespace Modules\Activities\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\Region;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivitiesPublicReadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_index_returns_only_published_activities_by_default(): void
    {
        [$organization, $location, $root, $leaf] = $this->createCatalogContext();

        $published = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "short_description" => "Публичная карточка.",
            ]);
        $draft = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->create([
                "name" => "Черновик",
                "slug" => "chernovik",
                "short_description" => "Не должен попасть в выдачу.",
                "status" => "draft",
            ]);

        DB::table("activity_categories")->insert([
            ["activity_id" => (string) $published->id, "category_id" => (string) $leaf->id],
            ["activity_id" => (string) $draft->id, "category_id" => (string) $leaf->id],
        ]);

        $this->getJson("/api/activities")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.id", (string) $published->id)
            ->assertJsonPath("data.data.0.primary_category.id", (string) $leaf->id)
            ->assertJsonPath("data.data.0.primary_category.parent.id", (string) $root->id);
    }

    #[Test]
    public function public_index_applies_root_category_filter(): void
    {
        [$organization, $location, $sportRoot, $footballLeaf] = $this->createCatalogContext();
        $musicRoot = Category::factory()->create([
            "name" => "Музыка",
            "slug" => "muzyka",
        ]);
        $vocalLeaf = Category::factory()
            ->childOf($musicRoot)
            ->create([
                "name" => "Вокал",
                "slug" => "vokal",
            ]);

        $football = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);
        $vocal = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Вокал",
                "slug" => "vokal",
            ]);

        DB::table("activity_categories")->insert([
            ["activity_id" => (string) $football->id, "category_id" => (string) $footballLeaf->id],
            ["activity_id" => (string) $vocal->id, "category_id" => (string) $vocalLeaf->id],
        ]);

        $this->getJson("/api/activities?root_category_id=" . $sportRoot->id)
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.id", (string) $football->id);
    }

    #[Test]
    public function featured_endpoint_returns_only_featured_published_activities(): void
    {
        [$organization, $location, , $leaf] = $this->createCatalogContext();

        $featured = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->featured()
            ->create([
                "name" => "Футбол featured",
                "slug" => "futbol-featured",
            ]);
        $regular = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол regular",
                "slug" => "futbol-regular",
            ]);

        DB::table("activity_categories")->insert([
            ["activity_id" => (string) $featured->id, "category_id" => (string) $leaf->id],
            ["activity_id" => (string) $regular->id, "category_id" => (string) $leaf->id],
        ]);

        $this->getJson("/api/activities/featured")
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.id", (string) $featured->id);
    }

    #[Test]
    public function featured_endpoint_applies_city_filter(): void
    {
        $country = Country::factory()->create(["name" => "Россия", "iso_code" => "RUS"]);
        $region = Region::factory()->create([
            "name" => "Центральный регион",
            "country_id" => (string) $country->id,
        ]);
        $moscow = City::factory()->create([
            "name" => "Москва",
            "country_id" => (string) $country->id,
            "region_id" => (string) $region->id,
        ]);
        $spb = City::factory()->create([
            "name" => "Санкт-Петербург",
            "country_id" => (string) $country->id,
            "region_id" => (string) $region->id,
        ]);

        $organization = Organization::factory()->create(["status" => "active"]);
        $moscowLocation = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
            "city_id" => (string) $moscow->id,
            "address" => "Москва, Пушкина 10",
        ]);
        $spbLocation = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
            "city_id" => (string) $spb->id,
            "address" => "Санкт-Петербург, Невский 1",
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

        $moscowFeatured = Activity::factory()
            ->forOrganizationLocation($organization, $moscowLocation)
            ->published()
            ->featured()
            ->create([
                "name" => "Футбол Москва",
                "slug" => "futbol-moskva",
            ]);
        $spbFeatured = Activity::factory()
            ->forOrganizationLocation($organization, $spbLocation)
            ->published()
            ->featured()
            ->create([
                "name" => "Футбол Питер",
                "slug" => "futbol-piter",
            ]);

        DB::table("activity_categories")->insert([
            [
                "activity_id" => (string) $moscowFeatured->id,
                "category_id" => (string) $leaf->id,
            ],
            [
                "activity_id" => (string) $spbFeatured->id,
                "category_id" => (string) $leaf->id,
            ],
        ]);

        $this->getJson("/api/activities/featured?city_id=" . $moscow->id)
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.id", (string) $moscowFeatured->id)
            ->assertJsonPath("data.0.location.city.id", (string) $moscow->id);
    }

    #[Test]
    public function feed_endpoint_returns_cursor_payload_for_published_activities(): void
    {
        [$organization, $location, , $leaf] = $this->createCatalogContext();

        $older = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Старый",
                "slug" => "staryj",
                "created_at" => now()->subDay(),
            ]);
        $newer = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Новый",
                "slug" => "novyj",
                "created_at" => now(),
            ]);

        DB::table("activity_categories")->insert([
            ["activity_id" => (string) $older->id, "category_id" => (string) $leaf->id],
            ["activity_id" => (string) $newer->id, "category_id" => (string) $leaf->id],
        ]);

        $response = $this->getJson("/api/activities/feed?limit=1")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.items")
            ->assertJsonPath("data.items.0.id", (string) $newer->id)
            ->json();

        $this->assertNotEmpty(data_get($response, "data.next_cursor"));

        $this->getJson(
            "/api/activities/feed?limit=1&cursor=" .
                urlencode((string) data_get($response, "data.next_cursor")),
        )
            ->assertOk()
            ->assertJsonPath("data.items.0.id", (string) $older->id);
    }

    #[Test]
    public function feed_endpoint_applies_category_filter_and_ascending_sort_direction(): void
    {
        [$organization, $location, $root, $footballLeaf] = $this->createCatalogContext();
        $volleyballLeaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Волейбол",
                "slug" => "volejbol",
            ]);

        $olderFootball = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол младшие",
                "slug" => "futbol-junior",
                "created_at" => now()->subDays(2),
            ]);
        $newerFootball = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол старшие",
                "slug" => "futbol-senior",
                "created_at" => now()->subDay(),
            ]);
        $volleyball = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Волейбол",
                "slug" => "volejbol",
                "created_at" => now(),
            ]);

        DB::table("activity_categories")->insert([
            [
                "activity_id" => (string) $olderFootball->id,
                "category_id" => (string) $footballLeaf->id,
            ],
            [
                "activity_id" => (string) $newerFootball->id,
                "category_id" => (string) $footballLeaf->id,
            ],
            [
                "activity_id" => (string) $volleyball->id,
                "category_id" => (string) $volleyballLeaf->id,
            ],
        ]);

        $this->getJson("/api/activities/feed?category_id=" . $footballLeaf->id . "&sort_dir=asc")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(2, "data.items")
            ->assertJsonPath("data.items.0.id", (string) $olderFootball->id)
            ->assertJsonPath("data.items.1.id", (string) $newerFootball->id);
    }

    #[Test]
    public function feed_endpoint_applies_root_category_filter(): void
    {
        [$organization, $location, $sportRoot, $footballLeaf] = $this->createCatalogContext();
        $musicRoot = Category::factory()->create([
            "name" => "Музыка",
            "slug" => "muzyka",
        ]);
        $vocalLeaf = Category::factory()
            ->childOf($musicRoot)
            ->create([
                "name" => "Вокал",
                "slug" => "vokal",
            ]);

        $football = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);
        $vocal = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Вокал",
                "slug" => "vokal",
            ]);

        DB::table("activity_categories")->insert([
            ["activity_id" => (string) $football->id, "category_id" => (string) $footballLeaf->id],
            ["activity_id" => (string) $vocal->id, "category_id" => (string) $vocalLeaf->id],
        ]);

        $this->getJson("/api/activities/feed?root_category_id=" . $sportRoot->id)
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.items")
            ->assertJsonPath("data.items.0.id", (string) $football->id);
    }

    #[Test]
    public function show_endpoint_resolves_activity_by_uuid_backed_public_key(): void
    {
        [$organization, $location, , $leaf] = $this->createCatalogContext();

        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Вокал",
                "slug" => "vokal",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        $this->getJson("/api/activities/" . $activity->slug . "-" . $activity->id)
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.id", (string) $activity->id)
            ->assertJsonPath("data.primary_category.id", (string) $leaf->id);
    }

    private function createCatalogContext(): array
    {
        $organization = Organization::factory()->create(["status" => "active"]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
            "address" => "Москва, Пушкина 10",
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
