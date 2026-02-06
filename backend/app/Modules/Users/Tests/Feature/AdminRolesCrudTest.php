<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\Users\Models\Role;

final class AdminRolesCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return '/api/admin/roles';
    }

    protected function table(): string
    {
        return 'roles';
    }

    protected function seedForList(int $count): void
    {
        Role::factory()->count($count)->create();
    }

    protected function seedForCreate(): void
    {
        // no-op
    }

    protected function createPayload(): array
    {
        return [
            'code' => 'editor',
            'label' => 'Editor',
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            'code' => 'editor',
            'label' => 'Editor',
        ];
    }

    protected function createItem(): mixed
    {
        return Role::factory()->create([
            'code' => 'editor',
            'label' => 'Editor',
        ]);
    }

    protected function itemId(mixed $item): string
    {
        return (string) $item->id;
    }

    protected function showIdPath(): string
    {
        return 'role.id';
    }

    protected function updatePayload(mixed $item): array
    {
        return [
            'label' => 'Updated Editor',
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            'id' => $item->id,
            'label' => 'Updated Editor',
        ];
    }

    protected function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth['user']->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
