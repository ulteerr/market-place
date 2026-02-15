<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;

final class AdminRolesCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return "/api/admin/roles";
    }

    protected function table(): string
    {
        return "roles";
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
            "code" => "editor",
            "label" => "Editor",
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            "code" => "editor",
            "label" => "Editor",
        ];
    }

    protected function createItem(): mixed
    {
        return Role::factory()->create([
            "code" => "editor",
            "label" => "Editor",
        ]);
    }

    protected function itemId(mixed $item): string
    {
        return (string) $item->id;
    }

    protected function showIdPath(): string
    {
        return "role.id";
    }

    protected function updatePayload(mixed $item): array
    {
        return [
            "label" => "Updated Editor",
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            "id" => $item->id,
            "label" => "Updated Editor",
        ];
    }

    protected function actingAsAdmin(): array
    {
        $superAdminRole = Role::factory()->superAdmin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$superAdminRole->id]);

        return $auth;
    }

    #[Test]
    public function regular_admin_can_create_and_delete_non_system_roles(): void
    {
        $adminRole = Role::factory()->admin()->create();
        $existingRole = Role::factory()->create([
            "code" => "temp-role",
            "label" => "Temp Role",
        ]);

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "code" => "editor",
                "label" => "Editor",
            ])
            ->assertCreated();

        $this->withHeaders($auth["headers"])
            ->deleteJson($this->endpoint() . "/" . $existingRole->id)
            ->assertOk()
            ->assertJsonPath("status", "ok");
    }

    #[Test]
    public function super_admin_can_manage_role_permissions(): void
    {
        $permissionRead = AccessPermission::query()->create([
            "code" => "admin.users.read",
            "scope" => "admin",
            "label" => "Read users",
        ]);
        $permissionUpdate = AccessPermission::query()->create([
            "code" => "admin.users.update",
            "scope" => "admin",
            "label" => "Update users",
        ]);

        $auth = $this->actingAsAdmin();

        $createResponse = $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "code" => "auditor",
                "label" => "Auditor",
                "permissions" => ["admin.users.read", "admin.users.update"],
            ])
            ->assertCreated();

        $roleId = (string) $createResponse->json("data.id");

        $this->assertDatabaseHas("role_access_permission", [
            "role_id" => $roleId,
            "permission_id" => (string) $permissionRead->id,
        ]);
        $this->assertDatabaseHas("role_access_permission", [
            "role_id" => $roleId,
            "permission_id" => (string) $permissionUpdate->id,
        ]);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/" . $roleId, [
                "permissions" => ["admin.users.read"],
            ])
            ->assertOk();

        $this->assertDatabaseHas("role_access_permission", [
            "role_id" => $roleId,
            "permission_id" => (string) $permissionRead->id,
        ]);
        $this->assertDatabaseMissing("role_access_permission", [
            "role_id" => $roleId,
            "permission_id" => (string) $permissionUpdate->id,
        ]);
    }

    #[Test]
    public function admin_can_list_available_access_permissions(): void
    {
        AccessPermission::query()->create([
            "code" => "admin.users.read",
            "scope" => "admin",
            "label" => "Read users",
        ]);

        $adminRole = Role::factory()->admin()->create();
        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/permissions")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.data.0.code", "admin.users.read");
    }
}
