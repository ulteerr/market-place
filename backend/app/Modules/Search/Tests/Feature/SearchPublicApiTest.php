<?php

declare(strict_types=1);

namespace Modules\Search\Tests\Feature;

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

final class SearchPublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set("search.enabled", true);
        config()->set("search.drivers.elasticsearch.enabled", false);
    }

    #[Test]
    public function suggest_endpoint_returns_queries_and_entities(): void
    {
        [$organization, $location, $root, $leaf] = $this->createContext();

        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбольная секция",
                "slug" => "futbolnaya-sekciya",
                "short_description" => "Тренировки для детей.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        $this->getJson("/api/search/suggest?q=фут")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.queries.0", "Футбольная секция")
            ->assertJsonPath("data.entities.0.type", "category")
            ->assertJsonPath("data.entities.0.url", "/catalog/sport/futbol");
    }

    #[Test]
    public function search_endpoint_returns_items_and_facets(): void
    {
        [$organization, $location, $root, $leaf] = $this->createContext();

        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->featured()
            ->create([
                "name" => "Футбол PRO",
                "slug" => "futbol-pro",
                "short_description" => "Сильная футбольная группа.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        $this->getJson("/api/search?q=футбол")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.items.0.id", (string) $activity->id)
            ->assertJsonPath("data.facets.organizations.0.id", (string) $organization->id)
            ->assertJsonPath("data.facets.categories.0.id", (string) $leaf->id);
    }

    #[Test]
    public function suggest_endpoint_applies_city_filter(): void
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
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "is_active" => true,
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "is_active" => true,
            ]);

        $moscowOrganization = Organization::factory()->create([
            "name" => "Футбол Москва",
            "status" => "active",
        ]);
        $spbOrganization = Organization::factory()->create([
            "name" => "Футбол Питер",
            "status" => "active",
        ]);
        $moscowLocation = OrganizationLocation::factory()->create([
            "organization_id" => (string) $moscowOrganization->id,
            "city_id" => (string) $moscow->id,
            "address" => "Москва, ул. Спортивная, 1",
        ]);
        $spbLocation = OrganizationLocation::factory()->create([
            "organization_id" => (string) $spbOrganization->id,
            "city_id" => (string) $spb->id,
            "address" => "Санкт-Петербург, Невский 1",
        ]);

        $moscowActivity = Activity::factory()
            ->forOrganizationLocation($moscowOrganization, $moscowLocation)
            ->published()
            ->create([
                "name" => "Футбол Москва",
                "slug" => "futbol-moskva",
                "short_description" => "Тренировки в Москве.",
            ]);
        $spbActivity = Activity::factory()
            ->forOrganizationLocation($spbOrganization, $spbLocation)
            ->published()
            ->create([
                "name" => "Футбол Питер",
                "slug" => "futbol-piter",
                "short_description" => "Тренировки в Санкт-Петербурге.",
            ]);

        DB::table("activity_categories")->insert([
            [
                "activity_id" => (string) $moscowActivity->id,
                "category_id" => (string) $leaf->id,
            ],
            [
                "activity_id" => (string) $spbActivity->id,
                "category_id" => (string) $leaf->id,
            ],
        ]);

        $this->getJson("/api/search/suggest?q=футбол&city_id=" . $moscow->id)
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.queries")
            ->assertJsonPath("data.queries.0", "Футбол Москва")
            ->assertJsonPath("data.entities.1.label", "Футбол Москва");
    }

    /**
     * @return array{0: Organization, 1: OrganizationLocation, 2: Category, 3: Category}
     */
    private function createContext(): array
    {
        $organization = Organization::factory()->create([
            "name" => "Футбол Академия",
            "status" => "active",
        ]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
            "address" => "Москва, ул. Спортивная, 1",
        ]);
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "is_active" => true,
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "is_active" => true,
            ]);

        return [$organization, $location, $root, $leaf];
    }
}
