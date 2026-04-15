<?php

declare(strict_types=1);

namespace Modules\Search\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
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
