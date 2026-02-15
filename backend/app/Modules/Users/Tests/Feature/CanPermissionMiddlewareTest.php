<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CanPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(["auth:sanctum", "can_permission:admin.users.read"])->get(
            "/api/test/permission-check",
            fn() => response()->json([
                "status" => "ok",
            ]),
        );
    }

    #[Test]
    public function user_without_permission_gets_forbidden(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->getJson("/api/test/permission-check")
            ->assertForbidden()
            ->assertJson([
                "status" => "error",
                "message" => "Forbidden",
            ]);
    }

    #[Test]
    public function user_with_permission_can_access_route(): void
    {
        $permission = AccessPermission::query()->create([
            "code" => "admin.users.read",
            "scope" => "admin",
            "label" => "Просмотр пользователей",
        ]);

        $role = Role::factory()->admin()->create();
        $role->permissions()->sync([$permission->id]);

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$role->id]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/test/permission-check")
            ->assertOk()
            ->assertJsonPath("status", "ok");
    }

    #[Test]
    public function user_override_allow_can_grant_permission_without_role(): void
    {
        AccessPermission::query()->create([
            "code" => "admin.users.read",
            "scope" => "admin",
            "label" => "Просмотр пользователей",
        ]);

        $auth = $this->actingAsUser();
        $auth["user"]->setPermissionOverride("admin.users.read", true);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/test/permission-check")
            ->assertOk()
            ->assertJsonPath("status", "ok");
    }

    #[Test]
    public function user_override_deny_has_priority_over_role_permission(): void
    {
        $permission = AccessPermission::query()->create([
            "code" => "admin.users.read",
            "scope" => "admin",
            "label" => "Просмотр пользователей",
        ]);

        $role = Role::factory()->admin()->create();
        $role->permissions()->sync([$permission->id]);

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$role->id]);
        $auth["user"]->setPermissionOverride("admin.users.read", false);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/test/permission-check")
            ->assertForbidden()
            ->assertJson([
                "status" => "error",
                "message" => "Forbidden",
            ]);
    }
}
