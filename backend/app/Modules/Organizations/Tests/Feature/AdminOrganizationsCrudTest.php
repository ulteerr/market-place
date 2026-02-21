<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\Organizations\Models\Organization;
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
}
