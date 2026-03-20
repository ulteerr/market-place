<?php

declare(strict_types=1);

namespace Modules\Activities\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Activities\Repositories\ActivitiesRepository;
use Modules\Activities\Services\ActivitiesService;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivitiesServiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function service_generates_unique_preview_slug_with_numeric_suffix(): void
    {
        [$organization, $location] = $this->createOrganizationContext();

        Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->create([
                "name" => "Футбольная секция",
                "slug" => "futbolnaya-sektsiya",
                "short_description" => "Уже существует.",
            ]);

        $service = app(ActivitiesService::class);

        $this->assertSame("futbolnaya-sektsiya-2", $service->previewSlug("Футбольная секция"));
    }

    #[Test]
    public function repository_resolves_activity_by_uuid_backed_public_route_key(): void
    {
        [$organization, $location, $root, $leaf] = $this->createCatalogContext();

        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "short_description" => "Публичная карточка.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        $repository = new ActivitiesRepository();

        $resolved = $repository->findByPublicRouteKey("futbol-" . $activity->id);

        $this->assertNotNull($resolved);
        $this->assertSame((string) $activity->id, (string) $resolved->id);
        $this->assertSame(
            (string) $leaf->id,
            (string) optional($resolved->primaryCategory->first())->id,
        );
        $this->assertSame(
            (string) $root->id,
            (string) optional(optional($resolved->primaryCategory->first())->parent)->id,
        );
    }

    private function createOrganizationContext(): array
    {
        $organization = Organization::factory()->create(["status" => "active"]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
        ]);

        return [$organization, $location];
    }

    private function createCatalogContext(): array
    {
        [$organization, $location] = $this->createOrganizationContext();
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
