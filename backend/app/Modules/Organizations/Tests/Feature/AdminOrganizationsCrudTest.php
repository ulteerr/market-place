<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\ActionLog\Models\ActionLog;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Metro\Models\MetroStation;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Users\Models\Role;

final class AdminOrganizationsCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return "/api/admin/organizations";
    }

    protected function table(): string
    {
        return "organizations";
    }

    protected function seedForList(int $count): void
    {
        for ($index = 0; $index <= $count; $index += 1) {
            Organization::factory()->create([
                "name" => "Организация{$index}",
                "description" => "Тест {$index}",
                "status" => "active",
                "source_type" => "manual",
                "ownership_status" => "unclaimed",
            ]);
        }
    }

    protected function seedForCreate(): void
    {
        // no-op
    }

    protected function createPayload(): array
    {
        $walkStation = MetroStation::factory()->create();
        $driveStation = MetroStation::factory()->create(["city_id" => $walkStation->city_id]);

        return [
            "name" => "Новая организация",
            "description" => "Описание",
            "email" => "org@example.com",
            "phone" => "+79990001122",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
            "locations" => [
                [
                    "address" => "Москва, ул. Первая, 1",
                    "lat" => 55.751244,
                    "lng" => 37.618423,
                    "metro_connections" => [
                        [
                            "metro_station_id" => (string) $walkStation->id,
                            "travel_mode" => "walk",
                            "duration_minutes" => 7,
                        ],
                        [
                            "metro_station_id" => (string) $driveStation->id,
                            "travel_mode" => "drive",
                            "duration_minutes" => 5,
                        ],
                    ],
                ],
                [
                    "address" => "Санкт-Петербург, ул. Вторая, 2",
                    "lat" => 59.93428,
                    "lng" => 30.335099,
                ],
            ],
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            "name" => "Новая организация",
            "email" => "org@example.com",
            "status" => "active",
        ];
    }

    protected function createItem(): mixed
    {
        return Organization::factory()->create([
            "name" => "До",
            "description" => "До",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);
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
            "name" => "После",
            "description" => "После",
            "ownership_status" => "claimed",
            "locations" => [
                [
                    "address" => "Казань, ул. Новая, 3",
                    "lat" => 55.78874,
                    "lng" => 49.12214,
                ],
            ],
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            "id" => (string) $item->id,
            "name" => "После",
            "ownership_status" => "claimed",
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
        $organization = Organization::query()->where("name", "Новая организация")->firstOrFail();
        $location = OrganizationLocation::query()
            ->where("organization_id", (string) $organization->id)
            ->where("address", "Москва, ул. Первая, 1")
            ->firstOrFail();

        $this->assertDatabaseHas("organization_locations", [
            "organization_id" => (string) $organization->id,
            "address" => "Москва, ул. Первая, 1",
        ]);
        $this->assertDatabaseHas("organization_locations", [
            "organization_id" => (string) $organization->id,
            "address" => "Санкт-Петербург, ул. Вторая, 2",
        ]);
        $this->assertDatabaseHas("organization_location_metro_stations", [
            "organization_location_id" => (string) $location->id,
            "travel_mode" => "walk",
            "duration_minutes" => 7,
        ]);
        $this->assertDatabaseHas("organization_location_metro_stations", [
            "organization_location_id" => (string) $location->id,
            "travel_mode" => "drive",
            "duration_minutes" => 5,
        ]);
    }

    public function test_update_replaces_locations(): void
    {
        $auth = $this->actingAsAdmin();
        $station = MetroStation::factory()->create();
        $organization = Organization::factory()->create([
            "name" => "Старое имя",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        OrganizationLocation::query()->create([
            "organization_id" => (string) $organization->id,
            "address" => "Старый адрес",
            "lat" => 55.0,
            "lng" => 37.0,
        ]);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/organizations/{$organization->id}", [
                "locations" => [
                    [
                        "address" => "Новый адрес",
                        "lat" => 56.0,
                        "lng" => 38.0,
                        "metro_connections" => [
                            [
                                "metro_station_id" => (string) $station->id,
                                "travel_mode" => "walk",
                                "duration_minutes" => 10,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseMissing("organization_locations", [
            "organization_id" => (string) $organization->id,
            "address" => "Старый адрес",
        ]);
        $this->assertDatabaseHas("organization_locations", [
            "organization_id" => (string) $organization->id,
            "address" => "Новый адрес",
        ]);

        $newLocation = OrganizationLocation::query()
            ->where("organization_id", (string) $organization->id)
            ->where("address", "Новый адрес")
            ->firstOrFail();

        $this->assertDatabaseHas("organization_location_metro_stations", [
            "organization_location_id" => (string) $newLocation->id,
            "metro_station_id" => (string) $station->id,
            "travel_mode" => "walk",
            "duration_minutes" => 10,
        ]);

        $changeLog = ChangeLog::query()
            ->where("auditable_type", Organization::class)
            ->where("auditable_id", (string) $organization->id)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($changeLog);
        $this->assertContains("locations", $changeLog->changed_fields ?? []);
        $this->assertSame("Старый адрес", $changeLog->before["locations"][0]["address"] ?? null);
        $this->assertSame("Новый адрес", $changeLog->after["locations"][0]["address"] ?? null);

        $actionLog = ActionLog::query()
            ->where("model_type", Organization::class)
            ->where("model_id", (string) $organization->id)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($actionLog);
        $this->assertContains("locations", $actionLog->changed_fields ?? []);
        $this->assertSame("Старый адрес", $actionLog->before["locations"][0]["address"] ?? null);
        $this->assertSame("Новый адрес", $actionLog->after["locations"][0]["address"] ?? null);
    }
}
