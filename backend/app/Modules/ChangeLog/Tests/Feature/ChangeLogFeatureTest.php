<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChangeLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_create_update_and_delete_for_user(): void
    {
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "Audit",
                "last_name" => "Target",
                "email" => "audit-target@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $target = User::query()->where("email", "audit-target@example.com")->firstOrFail();

        $createLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $target->id)
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($createLog);
        $this->assertSame((string) $auth["user"]->id, $createLog->actor_id);
        $this->assertNull($createLog->changed_fields);

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/" . $target->id, [
                "first_name" => "AuditUpdated",
            ])
            ->assertOk();

        $updateLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $target->id)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertSame((string) $auth["user"]->id, $updateLog->actor_id);
        $this->assertSame("Audit", $updateLog->before["first_name"] ?? null);
        $this->assertSame("AuditUpdated", $updateLog->after["first_name"] ?? null);
        $this->assertContains("first_name", $updateLog->changed_fields ?? []);

        $this->withHeaders($auth["headers"])
            ->delete("/api/admin/users/" . $target->id)
            ->assertOk();

        $deleteLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $target->id)
            ->where("event", "delete")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($deleteLog);
        $this->assertSame("AuditUpdated", $deleteLog->before["first_name"] ?? null);
    }

    #[Test]
    public function it_marks_me_profile_update_scope_in_changelog_meta(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->patch("/api/me", [
                "first_name" => "ScopedName",
            ])
            ->assertOk();

        $profileLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", (string) $auth["user"]->id)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($profileLog);
        $this->assertSame("profile", $profileLog->meta["scope"] ?? null);
        $this->assertNotContains("settings", $profileLog->changed_fields ?? []);
    }

    #[Test]
    public function admin_can_rollback_role_to_previous_version(): void
    {
        $auth = $this->actingAsAdmin();

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/roles", [
                "code" => "audit-role",
                "label" => "Role A",
            ])
            ->assertCreated();

        $roleId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/roles/{$roleId}", [
                "label" => "Role B",
            ])
            ->assertOk();

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/roles/{$roleId}", [
                "label" => "Role C",
            ])
            ->assertOk();

        $targetEntry = ChangeLog::query()
            ->where("auditable_type", Role::class)
            ->where("auditable_id", $roleId)
            ->where("event", "update")
            ->where("version", 3)
            ->firstOrFail();

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/changelog/{$targetEntry->id}/rollback")
            ->assertOk()
            ->assertJsonPath("data.model_id", $roleId);

        $this->assertDatabaseHas("roles", [
            "id" => $roleId,
            "label" => "Role B",
        ]);
    }

    #[Test]
    public function admin_can_restore_hard_deleted_role_via_rollback(): void
    {
        $auth = $this->actingAsAdmin();

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/roles", [
                "code" => "audit-delete-role",
                "label" => "Delete Me",
            ])
            ->assertCreated();

        $roleId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->delete("/api/admin/roles/{$roleId}")
            ->assertOk();

        $deleteEntry = ChangeLog::query()
            ->where("auditable_type", Role::class)
            ->where("auditable_id", $roleId)
            ->where("event", "delete")
            ->firstOrFail();

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/changelog/{$deleteEntry->id}/rollback")
            ->assertOk();

        $this->assertDatabaseHas("roles", [
            "id" => $roleId,
            "label" => "Delete Me",
        ]);
    }

    #[Test]
    public function non_admin_cannot_rollback_changes(): void
    {
        $user = $this->actingAsUser();
        $participant = Role::factory()->participant()->create();
        $user["user"]->roles()->sync([$participant->id]);
        $role = Role::factory()->create([
            "code" => "rollback-denied",
            "label" => "Denied",
        ]);
        $entry = ChangeLog::query()->create([
            "auditable_type" => Role::class,
            "auditable_id" => (string) $role->id,
            "event" => "create",
            "version" => 1,
            "before" => null,
            "after" => [
                "id" => (string) $role->id,
                "code" => "rollback-denied",
                "label" => "Denied",
                "is_system" => false,
            ],
            "changed_fields" => ["id", "code", "label", "is_system"],
        ]);

        $this->withHeaders($user["headers"])
            ->post("/api/admin/changelog/{$entry->id}/rollback")
            ->assertForbidden();
    }

    #[Test]
    public function it_does_not_log_create_without_authenticated_actor(): void
    {
        $role = Role::factory()->create([
            "code" => "without-actor",
            "label" => "Without Actor",
        ]);

        $this->assertDatabaseMissing("change_logs", [
            "auditable_type" => Role::class,
            "auditable_id" => (string) $role->id,
            "event" => "create",
        ]);
    }

    #[Test]
    public function it_skips_update_log_when_no_actual_changes(): void
    {
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "NoChange",
                "last_name" => "User",
                "email" => "no-change@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");

        $beforeCount = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->count();

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/{$userId}", [
                "first_name" => "NoChange",
            ])
            ->assertOk();

        $afterCount = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->count();

        $this->assertSame($beforeCount, $afterCount);
    }

    private function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();
        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
