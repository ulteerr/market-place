<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminCacheResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_reset_backend_cache_and_frontend_ssr_artifacts(): void
    {
        $permission = AccessPermission::query()->create([
            "code" => "admin.panel.access",
            "scope" => "admin",
            "label" => "Доступ в админ-панель",
        ]);
        $role = Role::factory()->admin()->create();
        $role->permissions()->sync([$permission->id]);

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$role->id]);

        $frontendRoot = storage_path("framework/testing/admin-cache-reset-frontend");
        File::deleteDirectory($frontendRoot);
        File::ensureDirectoryExists($frontendRoot . "/.nuxt");
        File::ensureDirectoryExists($frontendRoot . "/.output");
        File::ensureDirectoryExists($frontendRoot . "/node_modules/.cache/nuxt");
        File::put($frontendRoot . "/.nuxt/test.txt", "nuxt");
        File::put($frontendRoot . "/.output/test.txt", "output");
        File::put($frontendRoot . "/node_modules/.cache/nuxt/test.txt", "cache");

        config()->set("admin-cache-reset.frontend_root", $frontendRoot);
        Cache::put("admin-cache-reset-test", "ok", 300);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/cache/reset", [
                "scopes" => ["frontend-ssr", "backend"],
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.status", "completed")
            ->assertJsonPath("data.scopes.0", "frontend-ssr")
            ->assertJsonPath("data.scopes.1", "backend");

        $this->assertFalse(File::exists($frontendRoot . "/.nuxt"));
        $this->assertFalse(File::exists($frontendRoot . "/.output"));
        $this->assertFalse(File::exists($frontendRoot . "/node_modules/.cache/nuxt"));
        $this->assertNull(Cache::get("admin-cache-reset-test"));
    }

    #[Test]
    public function non_admin_user_cannot_reset_cache(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/cache/reset", [
                "scopes" => ["frontend-ssr", "backend"],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function it_validates_requested_scopes(): void
    {
        $permission = AccessPermission::query()->create([
            "code" => "admin.panel.access",
            "scope" => "admin",
            "label" => "Доступ в админ-панель",
        ]);
        $role = Role::factory()->admin()->create();
        $role->permissions()->sync([$permission->id]);

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$role->id]);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/cache/reset", [
                "scopes" => ["unknown-scope"],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["scopes.0"]);
    }
}
