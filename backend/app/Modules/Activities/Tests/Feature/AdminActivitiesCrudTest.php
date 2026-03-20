<?php

declare(strict_types=1);

namespace Modules\Activities\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;

final class AdminActivitiesCrudTest extends AdminCrudTestCase
{
    private Organization $organization;
    private OrganizationLocation $location;
    private Category $rootCategory;
    private Category $leafCategory;

    protected function endpoint(): string
    {
        return "/api/admin/activities";
    }

    protected function table(): string
    {
        return "activities";
    }

    protected function seedForList(int $count): void
    {
        $context = $this->ensureContext();

        for ($index = 0; $index <= $count; $index += 1) {
            $activity = Activity::factory()
                ->forOrganizationLocation($context["organization"], $context["location"])
                ->create([
                    "name" => "Активность {$index}",
                    "slug" => "aktivnost-{$index}",
                    "short_description" => "Описание {$index}",
                    "status" => "published",
                ]);

            DB::table("activity_categories")->insert([
                "activity_id" => (string) $activity->id,
                "category_id" => (string) $context["leaf"]->id,
            ]);
        }
    }

    protected function seedForCreate(): void
    {
        $this->ensureContext();
    }

    protected function createPayload(): array
    {
        $context = $this->ensureContext();

        return [
            "organization_id" => (string) $context["organization"]->id,
            "location_id" => (string) $context["location"]->id,
            "category_id" => (string) $context["leaf"]->id,
            "name" => "Футбольная секция",
            "slug" => "futbolnaya-sektsiya",
            "short_description" => "Групповые тренировки для детей.",
            "description" => "Подробное описание секции.",
            "min_age" => 7,
            "max_age" => 12,
            "capacity" => 18,
            "price_from" => 1500,
            "price_to" => 2500,
            "currency" => "RUB",
            "status" => "published",
            "is_featured" => true,
            "published_at" => now()->toISOString(),
            "schedules" => [
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
            ],
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            "name" => "Футбольная секция",
            "slug" => "futbolnaya-sektsiya",
            "short_description" => "Групповые тренировки для детей.",
            "status" => "published",
            "is_featured" => true,
        ];
    }

    protected function createItem(): mixed
    {
        $context = $this->ensureContext();
        $activity = Activity::factory()
            ->forOrganizationLocation($context["organization"], $context["location"])
            ->create([
                "name" => "Вокальная студия",
                "slug" => "vokalnaya-studiya",
                "short_description" => "Музыкальные занятия.",
                "status" => "draft",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $context["leaf"]->id,
        ]);

        return $activity;
    }

    protected function itemId(mixed $item): string
    {
        return (string) $item->id;
    }

    protected function showIdPath(): string
    {
        return "data.id";
    }

    protected function updatePayload(mixed $item): array
    {
        return [
            "name" => "Вокальная студия updated",
            "slug" => "vokalnaya-studiya-updated",
            "short_description" => "Музыкальные занятия updated.",
            "status" => "pending_review",
            "is_featured" => false,
            "schedules" => [
                [
                    "day_of_week" => 6,
                    "start_time" => "12:00:00",
                    "end_time" => "13:30:00",
                ],
            ],
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            "id" => (string) $item->id,
            "name" => "Вокальная студия updated",
            "slug" => "vokalnaya-studiya-updated",
            "short_description" => "Музыкальные занятия updated.",
            "status" => "pending_review",
            "is_featured" => false,
        ];
    }

    protected function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }

    protected function afterCreateAssertions(): void
    {
        $activity = Activity::query()->where("slug", "futbolnaya-sektsiya")->firstOrFail();

        $this->assertDatabaseHas("activity_categories", [
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $this->leafCategory->id,
        ]);
        $this->assertDatabaseCount("activity_schedules", 2);
    }

    #[Test]
    public function admin_cannot_assign_root_category_to_activity(): void
    {
        $auth = $this->actingAsAdmin();
        $context = $this->ensureContext();
        $payload = $this->createPayload();
        $payload["category_id"] = (string) $context["root"]->id;

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["category_id"]);
    }

    #[Test]
    public function admin_cannot_assign_location_from_another_organization(): void
    {
        $auth = $this->actingAsAdmin();
        $context = $this->ensureContext();
        $foreignOrganization = Organization::factory()->create();
        $foreignLocation = OrganizationLocation::factory()->create([
            "organization_id" => (string) $foreignOrganization->id,
        ]);

        $payload = $this->createPayload();
        $payload["location_id"] = (string) $foreignLocation->id;

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["location_id"]);
    }

    #[Test]
    public function admin_can_preview_unique_activity_slug(): void
    {
        $auth = $this->actingAsAdmin();
        $context = $this->ensureContext();

        Activity::factory()
            ->forOrganizationLocation($context["organization"], $context["location"])
            ->create([
                "name" => "Футбольная секция",
                "slug" => "futbolnaya-sektsiya",
                "short_description" => "Музыкальные занятия.",
            ]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/activities/slug-preview?name=Футбольная секция")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.slug", "futbolnaya-sektsiya-2");
    }

    #[Test]
    public function admin_cannot_create_activity_with_duplicate_schedule_slot(): void
    {
        $auth = $this->actingAsAdmin();
        $payload = $this->createPayload();
        $payload["schedules"] = [
            [
                "day_of_week" => 2,
                "start_time" => "10:00:00",
                "end_time" => "11:00:00",
            ],
            [
                "day_of_week" => 2,
                "start_time" => "10:00:00",
                "end_time" => "11:00:00",
            ],
        ];

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["schedules.1.day_of_week"]);
    }

    #[Test]
    public function admin_cannot_create_activity_with_invalid_schedule_time_and_ranges(): void
    {
        $auth = $this->actingAsAdmin();
        $payload = $this->createPayload();
        $payload["min_age"] = 12;
        $payload["max_age"] = 7;
        $payload["price_from"] = 2500;
        $payload["price_to"] = 1500;
        $payload["schedules"] = [
            [
                "day_of_week" => 2,
                "start_time" => "11:00:00",
                "end_time" => "10:00:00",
            ],
        ];

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["max_age", "price_to", "schedules.0.end_time"]);
    }

    #[Test]
    public function admin_update_replaces_primary_category_and_schedules(): void
    {
        $auth = $this->actingAsAdmin();
        $context = $this->ensureContext();
        $newLeaf = Category::factory()
            ->childOf($context["root"])
            ->create([
                "name" => "Волейбол",
                "slug" => "volejbol",
            ]);

        $activity = Activity::factory()
            ->forOrganizationLocation($context["organization"], $context["location"])
            ->create([
                "name" => "Смена категории",
                "slug" => "smena-kategorii",
                "short_description" => "Проверка обновления.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $context["leaf"]->id,
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
            ->patchJson("{$this->endpoint()}/{$activity->id}", [
                "category_id" => (string) $newLeaf->id,
                "schedules" => [
                    [
                        "day_of_week" => 6,
                        "start_time" => "12:00:00",
                        "end_time" => "13:30:00",
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.primary_category.id", (string) $newLeaf->id);

        $this->assertDatabaseHas("activity_categories", [
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $newLeaf->id,
        ]);
        $this->assertDatabaseMissing("activity_categories", [
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $context["leaf"]->id,
        ]);
        $this->assertDatabaseCount("activity_schedules", 1);
        $this->assertDatabaseHas("activity_schedules", [
            "activity_id" => (string) $activity->id,
            "day_of_week" => 6,
            "start_time" => "12:00:00",
            "end_time" => "13:30:00",
        ]);
    }

    private function ensureContext(): array
    {
        if (isset($this->organization, $this->location, $this->rootCategory, $this->leafCategory)) {
            return [
                "organization" => $this->organization,
                "location" => $this->location,
                "root" => $this->rootCategory,
                "leaf" => $this->leafCategory,
            ];
        }

        $this->organization = Organization::factory()->create([
            "name" => "Организация спорта",
            "status" => "active",
        ]);
        $this->location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $this->organization->id,
            "address" => "Москва, Ленина 1",
        ]);
        $this->rootCategory = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
        ]);
        $this->leafCategory = Category::factory()
            ->childOf($this->rootCategory)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        return [
            "organization" => $this->organization,
            "location" => $this->location,
            "root" => $this->rootCategory,
            "leaf" => $this->leafCategory,
        ];
    }
}
