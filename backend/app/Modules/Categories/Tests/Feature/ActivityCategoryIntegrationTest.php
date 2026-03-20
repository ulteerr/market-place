<?php

declare(strict_types=1);

namespace Modules\Categories\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivityCategoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_activity_with_leaf_category_and_receive_root_leaf_pair_in_response(): void
    {
        $auth = $this->actingAsAdmin();
        [$organization, $location, $root, $leaf] = $this->createContext();

        $response = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/activities", [
                "organization_id" => (string) $organization->id,
                "location_id" => (string) $location->id,
                "category_id" => (string) $leaf->id,
                "name" => "Футбольная секция",
                "slug" => "futbolnaya-sektsiya",
                "short_description" => "Групповые тренировки для детей.",
                "status" => "published",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.primary_category.slug", "futbol")
            ->assertJsonPath("data.primary_category.parent.slug", "sport");

        $activityId = (string) $response->json("data.id");

        $this->assertDatabaseHas("activity_categories", [
            "activity_id" => $activityId,
            "category_id" => (string) $leaf->id,
        ]);

        $this->getJson("/api/activities/futbolnaya-sektsiya-" . $activityId)
            ->assertOk()
            ->assertJsonPath("data.primary_category.slug", "futbol")
            ->assertJsonPath("data.primary_category.parent.slug", "sport");
    }

    #[Test]
    public function updating_activity_category_replaces_previous_primary_leaf_assignment(): void
    {
        $auth = $this->actingAsAdmin();
        [$organization, $location, $rootA, $leafA] = $this->createContext();
        $rootB = Category::factory()->create([
            "name" => "Творчество",
            "slug" => "tvorchestvo",
            "parent_id" => null,
            "is_active" => true,
        ]);
        $leafB = Category::factory()
            ->childOf($rootB)
            ->create([
                "name" => "Музыка",
                "slug" => "muzyka",
                "is_active" => true,
            ]);

        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->create([
                "name" => "Футбольная секция",
                "slug" => "futbolnaya-sektsiya",
                "short_description" => "Групповые тренировки для детей.",
                "status" => "published",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leafA->id,
        ]);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/activities/" . $activity->id, [
                "category_id" => (string) $leafB->id,
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.primary_category.slug", "muzyka")
            ->assertJsonPath("data.primary_category.parent.slug", "tvorchestvo");

        $this->assertSame(
            1,
            DB::table("activity_categories")->where("activity_id", (string) $activity->id)->count(),
        );

        $this->assertDatabaseHas("activity_categories", [
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leafB->id,
        ]);
    }

    /**
     * @return array{0: Organization, 1: OrganizationLocation, 2: Category, 3: Category}
     */
    private function createContext(): array
    {
        $organization = Organization::factory()->create([
            "name" => "Организация спорта",
            "status" => "active",
        ]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
            "address" => "Москва, Ленина 1",
        ]);
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
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

    private function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
