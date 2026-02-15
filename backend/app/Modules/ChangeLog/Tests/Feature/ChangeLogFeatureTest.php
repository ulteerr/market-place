<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Files\Models\File;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ChangeLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function fakePng(string $name = "avatar.png"): UploadedFile
    {
        $png = base64_decode(
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlH0J8AAAAASUVORK5CYII=",
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $png ?: "");
    }

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
    public function it_logs_related_user_changes_for_roles(): void
    {
        $auth = $this->actingAsAdmin();

        Role::query()->firstOrCreate(
            ["code" => "participant"],
            ["label" => "Participant", "is_system" => true],
        );
        Role::query()->firstOrCreate(
            ["code" => "admin"],
            ["label" => "Admin", "is_system" => true],
        );

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "Related",
                "last_name" => "Audit",
                "email" => "related-audit@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/{$userId}", [
                "roles" => ["admin"],
            ])
            ->assertOk();

        $relatedLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->whereJsonContains("changed_fields", "roles")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($relatedLog);
        $this->assertSame("user-related", $relatedLog->meta["scope"] ?? null);
        $this->assertSame(["participant"], $relatedLog->before["roles"] ?? null);
        $this->assertSame(["admin", "participant"], $relatedLog->after["roles"] ?? null);
    }

    #[Test]
    public function it_merges_user_and_roles_changes_into_single_update_log(): void
    {
        $auth = $this->actingAsAdmin();

        Role::query()->firstOrCreate(
            ["code" => "participant"],
            ["label" => "Participant", "is_system" => true],
        );
        Role::query()->firstOrCreate(
            ["code" => "admin"],
            ["label" => "Admin", "is_system" => true],
        );

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "Merge",
                "last_name" => "Target",
                "email" => "merge-target@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/{$userId}", [
                "last_name" => "Target2",
                "roles" => ["admin"],
            ])
            ->assertOk();

        $updateLogs = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->orderByDesc("version")
            ->get();

        $this->assertCount(1, $updateLogs);

        $log = $updateLogs->first();
        $this->assertContains("last_name", $log->changed_fields ?? []);
        $this->assertContains("roles", $log->changed_fields ?? []);
        $this->assertSame("Target", $log->before["last_name"] ?? null);
        $this->assertSame("Target2", $log->after["last_name"] ?? null);
        $this->assertSame(["participant"], $log->before["roles"] ?? null);
        $this->assertSame(["admin", "participant"], $log->after["roles"] ?? null);
    }

    #[Test]
    public function admin_can_rollback_user_roles_from_related_log(): void
    {
        $auth = $this->actingAsAdmin();

        Role::query()->firstOrCreate(
            ["code" => "participant"],
            ["label" => "Participant", "is_system" => true],
        );
        Role::query()->firstOrCreate(
            ["code" => "admin"],
            ["label" => "Admin", "is_system" => true],
        );

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "Rollback",
                "last_name" => "Roles",
                "email" => "rollback-roles@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/{$userId}", [
                "roles" => ["admin"],
            ])
            ->assertOk();

        $rolesLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->whereJsonContains("changed_fields", "roles")
            ->latest("created_at")
            ->firstOrFail();

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/changelog/{$rolesLog->id}/rollback")
            ->assertOk();

        $user = User::query()->with("roles:id,code")->findOrFail($userId);
        $codes = $user->roles->pluck("code")->sort()->values()->all();

        $this->assertSame(["participant"], $codes);

        $restoreLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->where("rolled_back_from_id", $rolesLog->id)
            ->latest("version")
            ->first();

        $this->assertNotNull($restoreLog);
        $this->assertContains("roles", $restoreLog->changed_fields ?? []);
        $this->assertSame(["admin", "participant"], $restoreLog->before["roles"] ?? null);
        $this->assertSame(["participant"], $restoreLog->after["roles"] ?? null);
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
    public function admin_can_rollback_role_to_create_snapshot(): void
    {
        $auth = $this->actingAsAdmin();

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/roles", [
                "code" => "audit-create-snapshot",
                "label" => "Role Initial",
            ])
            ->assertCreated();

        $roleId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/roles/{$roleId}", [
                "label" => "Role Changed",
            ])
            ->assertOk();

        $createEntry = ChangeLog::query()
            ->where("auditable_type", Role::class)
            ->where("auditable_id", $roleId)
            ->where("event", "create")
            ->where("version", 1)
            ->firstOrFail();

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/changelog/{$createEntry->id}/rollback")
            ->assertOk()
            ->assertJsonPath("data.model_id", $roleId);

        $this->assertDatabaseHas("roles", [
            "id" => $roleId,
            "label" => "Role Initial",
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
    public function deleting_changelog_cleans_snapshot_file_when_no_other_references(): void
    {
        Storage::fake("public");

        $path = "uploads/old/changelog-cleanup/avatar.png";
        Storage::disk("public")->put($path, "snapshot");

        $file = File::query()->create([
            "disk" => "public",
            "path" => $path,
            "original_name" => "avatar.png",
            "mime_type" => "image/png",
            "size" => 8,
            "collection" => "avatar",
            "fileable_type" => null,
            "fileable_id" => null,
        ]);

        $entry = ChangeLog::query()->create([
            "auditable_type" => User::class,
            "auditable_id" => (string) User::factory()->create()->id,
            "event" => "update",
            "version" => 1,
            "before" => ["avatar_id" => (string) $file->id],
            "after" => ["avatar_id" => null],
            "media_before" => [
                "avatar" => [
                    "file_id" => (string) $file->id,
                    "disk" => "public",
                    "path" => $path,
                    "collection" => "avatar",
                ],
            ],
            "media_after" => ["avatar" => null],
            "changed_fields" => ["avatar_id"],
        ]);

        $this->assertDatabaseHas("file_references", [
            "file_id" => (string) $file->id,
            "owner_type" => "changelog:before",
            "owner_id" => (string) $entry->id,
        ]);

        $entry->delete();

        $this->assertDatabaseMissing("file_references", [
            "file_id" => (string) $file->id,
            "owner_type" => "changelog:before",
            "owner_id" => (string) $entry->id,
        ]);
        $this->assertDatabaseMissing("files", [
            "id" => (string) $file->id,
        ]);
        Storage::disk("public")->assertMissing($path);
    }

    #[Test]
    public function rollback_does_not_fail_when_avatar_snapshot_file_is_unavailable(): void
    {
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "Rollback",
                "last_name" => "MissingAvatar",
                "email" => "rollback-missing-avatar@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");

        $entry = ChangeLog::query()->create([
            "auditable_type" => User::class,
            "auditable_id" => $userId,
            "event" => "update",
            "version" => 999,
            "before" => ["avatar_id" => "73387241-b9f0-4c34-9053-f0a54282d0d5"],
            "after" => ["avatar_id" => null],
            "media_before" => [
                "avatar" => [
                    "file_id" => "73387241-b9f0-4c34-9053-f0a54282d0d5",
                    "disk" => "public",
                    "path" => "uploads/missing/file.png",
                    "collection" => "avatar",
                ],
            ],
            "media_after" => ["avatar" => null],
            "changed_fields" => ["avatar_id"],
        ]);

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/changelog/{$entry->id}/rollback")
            ->assertOk();

        $user = User::query()->with("avatar")->findOrFail($userId);
        $this->assertNull($user->avatar);
    }

    #[Test]
    public function rollback_avatar_change_creates_restore_log_entry(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();

        $createResponse = $this->withHeaders($auth["headers"])
            ->post("/api/admin/users", [
                "first_name" => "Avatar",
                "last_name" => "Rollback",
                "email" => "avatar-rollback-log@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");
        $user = User::query()->findOrFail($userId);

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/{$userId}", [
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "avatar" => $this->fakePng("a.png"),
            ])
            ->assertOk();

        $this->withHeaders($auth["headers"])
            ->patch("/api/admin/users/{$userId}", [
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "avatar" => $this->fakePng("b.png"),
            ])
            ->assertOk();

        $avatarUpdateLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->whereJsonContains("changed_fields", "avatar_id")
            ->latest("created_at")
            ->firstOrFail();

        $this->withHeaders($auth["headers"])
            ->post("/api/admin/changelog/{$avatarUpdateLog->id}/rollback")
            ->assertOk();

        $restoreLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->where("rolled_back_from_id", (string) $avatarUpdateLog->id)
            ->latest("created_at")
            ->first();

        $this->assertNotNull($restoreLog);
        $this->assertContains("avatar_id", $restoreLog->changed_fields ?? []);
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
