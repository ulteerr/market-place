<?php

declare(strict_types=1);

namespace Modules\ActionLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ActionLog\Models\ActionLog;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActionLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_model_create_update_delete_events(): void
    {
        $actor = User::query()->create([
            "email" => "actor@example.com",
            "first_name" => "Actor",
            "last_name" => "Admin",
            "password" => "password123",
        ]);
        $this->be($actor);

        $created = User::query()->create([
            "email" => "target@example.com",
            "first_name" => "Target",
            "last_name" => "User",
            "password" => "password123",
        ]);

        $createEntry = ActionLog::query()
            ->where("event", "create")
            ->where("model_type", User::class)
            ->where("model_id", (string) $created->id)
            ->latest("created_at")
            ->first();

        $this->assertNotNull($createEntry);
        $this->assertSame((string) $actor->id, (string) $createEntry->user_id);
        $this->assertSame("target@example.com", $createEntry->after["email"] ?? null);

        $created->update(["first_name" => "Updated"]);

        $updateEntry = ActionLog::query()
            ->where("event", "update")
            ->where("model_type", User::class)
            ->where("model_id", (string) $created->id)
            ->orderByDesc("created_at")
            ->first();

        $this->assertNotNull($updateEntry);
        $this->assertContains("first_name", $updateEntry->changed_fields ?? []);
        $this->assertSame("Target", $updateEntry->before["first_name"] ?? null);
        $this->assertSame("Updated", $updateEntry->after["first_name"] ?? null);

        $created->delete();

        $deleteEntry = ActionLog::query()
            ->where("event", "delete")
            ->where("model_type", User::class)
            ->where("model_id", (string) $created->id)
            ->orderByDesc("created_at")
            ->first();

        $this->assertNotNull($deleteEntry);
        $this->assertSame("Updated", $deleteEntry->before["first_name"] ?? null);
        $this->assertNull($deleteEntry->after);
    }

    #[Test]
    public function admin_can_list_action_logs_with_filters(): void
    {
        $auth = $this->actingAsAdmin();

        ActionLog::query()->create([
            "user_id" => $auth["user"]->id,
            "event" => "create",
            "model_type" => User::class,
            "model_id" => (string) $auth["user"]->id,
            "ip_address" => "127.0.0.1",
            "before" => null,
            "after" => ["email" => "created@example.com"],
            "changed_fields" => null,
        ]);

        ActionLog::query()->create([
            "user_id" => $auth["user"]->id,
            "event" => "update",
            "model_type" => Role::class,
            "model_id" => "role-1",
            "ip_address" => "127.0.0.1",
            "before" => ["label" => "Old"],
            "after" => ["label" => "New"],
            "changed_fields" => ["label"],
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson(
                "/api/admin/action-logs?event=update&search=Role&user=" .
                    urlencode((string) $auth["user"]->email),
            )
            ->assertOk()
            ->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.event", "update")
            ->assertJsonPath("data.data.0.model_type", Role::class);

        $response = $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/action-logs?user=" . urlencode("Тест Админ"))
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, (int) $response->json("data.total"));

        $modelFilteredResponse = $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/action-logs?model=user")
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, (int) $modelFilteredResponse->json("data.total"));
        $this->assertSame(
            User::class,
            (string) $modelFilteredResponse->json("data.data.0.model_type"),
        );
    }

    private function actingAsAdmin(): array
    {
        $participantRole = Role::query()->firstOrCreate(
            ["code" => "participant"],
            ["label" => "Participant", "is_system" => true],
        );
        $adminRole = Role::query()->firstOrCreate(
            ["code" => "admin"],
            ["label" => "Admin", "is_system" => true],
        );

        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];
        $user->update([
            "first_name" => "Тест",
            "last_name" => "Админ",
        ]);
        $user->roles()->syncWithoutDetaching([$participantRole->id, $adminRole->id]);
        $user->unsetRelation("roles");
        $user->load("roles");

        return $auth;
    }
}
