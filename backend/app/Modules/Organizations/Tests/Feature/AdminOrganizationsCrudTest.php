<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
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

        $this->assertDatabaseHas("organization_locations", [
            "organization_id" => (string) $organization->id,
            "address" => "Москва, ул. Первая, 1",
        ]);
        $this->assertDatabaseHas("organization_locations", [
            "organization_id" => (string) $organization->id,
            "address" => "Санкт-Петербург, ул. Вторая, 2",
        ]);
    }

    public function test_update_replaces_locations(): void
    {
        $auth = $this->actingAsAdmin();
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
    }
}
