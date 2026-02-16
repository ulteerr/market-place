<?php

declare(strict_types=1);

namespace Modules\Children\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\ActionLog\Models\ActionLog;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Children\Models\Child;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Schema;

final class AdminChildrenCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return "/api/admin/children";
    }

    protected function table(): string
    {
        return "children";
    }

    protected function seedForList(int $count): void
    {
        $parent = User::factory()->create();
        Child::query()->create([
            "user_id" => (string) $parent->id,
            "first_name" => "Артем",
            "last_name" => "Иванов",
            "middle_name" => "Сергеевич",
            "gender" => "male",
            "birth_date" => "2018-01-01",
        ]);

        for ($index = 0; $index < $count; $index += 1) {
            $user = User::factory()->create();
            Child::query()->create([
                "user_id" => (string) $user->id,
                "first_name" => "Ребенок{$index}",
                "last_name" => "Тестов",
                "middle_name" => "Иванович",
                "gender" => $index % 2 === 0 ? "male" : "female",
                "birth_date" => "2017-01-0" . (($index % 9) + 1),
            ]);
        }
    }

    protected function seedForCreate(): void
    {
        // no-op
    }

    protected function createPayload(): array
    {
        $parent = User::factory()->create();

        return [
            "user_id" => (string) $parent->id,
            "first_name" => "Новый",
            "last_name" => "Ребенок",
            "middle_name" => "Павлович",
            "gender" => "male",
            "birth_date" => "2016-03-14",
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            "first_name" => "Новый",
            "last_name" => "Ребенок",
            "middle_name" => "Павлович",
            "gender" => "male",
            "birth_date" => "2016-03-14",
        ];
    }

    protected function createItem(): mixed
    {
        $parent = User::factory()->create();

        return Child::query()->create([
            "user_id" => (string) $parent->id,
            "first_name" => "До",
            "last_name" => "Изменения",
            "middle_name" => "Иванович",
            "gender" => "male",
            "birth_date" => "2015-09-01",
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
            "first_name" => "После",
            "last_name" => "Изменения",
            "middle_name" => "Петрович",
            "gender" => "female",
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            "id" => (string) $item->id,
            "first_name" => "После",
            "last_name" => "Изменения",
            "middle_name" => "Петрович",
            "gender" => "female",
        ];
    }

    protected function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }

    #[Test]
    public function admin_actions_are_written_to_action_log_for_child_crud(): void
    {
        if (!Schema::hasTable("action_logs")) {
            $this->markTestSkipped("Action log table is not available in this module test suite.");
        }

        $auth = $this->actingAsAdmin();
        $parent = User::factory()->create();

        $createResponse = $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "user_id" => (string) $parent->id,
                "first_name" => "Лог",
                "last_name" => "Действий",
                "middle_name" => "Тестович",
                "gender" => "male",
                "birth_date" => "2016-04-12",
            ])
            ->assertCreated();

        $childId = (string) $createResponse->json("data.id");

        $this->assertDatabaseHas("action_logs", [
            "event" => "create",
            "model_type" => Child::class,
            "model_id" => $childId,
            "user_id" => (string) $auth["user"]->id,
        ]);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/{$childId}", [
                "first_name" => "Обновленный",
                "gender" => "female",
            ])
            ->assertOk();

        $updateEntry = ActionLog::query()
            ->where("event", "update")
            ->where("model_type", Child::class)
            ->where("model_id", $childId)
            ->latest("created_at")
            ->first();

        $this->assertNotNull($updateEntry);
        $this->assertContains("first_name", $updateEntry->changed_fields ?? []);
        $this->assertContains("gender", $updateEntry->changed_fields ?? []);
        $this->assertSame("Лог", $updateEntry->before["first_name"] ?? null);
        $this->assertSame("Обновленный", $updateEntry->after["first_name"] ?? null);
        $this->assertSame("male", $updateEntry->before["gender"] ?? null);
        $this->assertSame("female", $updateEntry->after["gender"] ?? null);

        $this->withHeaders($auth["headers"])
            ->deleteJson($this->endpoint() . "/{$childId}")
            ->assertOk();

        $this->assertDatabaseHas("action_logs", [
            "event" => "delete",
            "model_type" => Child::class,
            "model_id" => $childId,
            "user_id" => (string) $auth["user"]->id,
        ]);
    }

    #[Test]
    public function admin_actions_are_written_to_changelog_for_child_crud(): void
    {
        $auth = $this->actingAsAdmin();
        $parent = User::factory()->create();

        $createResponse = $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "user_id" => (string) $parent->id,
                "first_name" => "Лог",
                "last_name" => "Изменений",
                "middle_name" => "Тестович",
                "gender" => "male",
                "birth_date" => "2018-01-10",
            ])
            ->assertCreated();

        $childId = (string) $createResponse->json("data.id");

        $createEntry = ChangeLog::query()
            ->where("auditable_type", Child::class)
            ->where("auditable_id", $childId)
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($createEntry);
        $this->assertSame((string) $auth["user"]->id, (string) $createEntry->actor_id);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/{$childId}", [
                "first_name" => "Обновленный",
                "gender" => "female",
            ])
            ->assertOk();

        $updateEntry = ChangeLog::query()
            ->where("auditable_type", Child::class)
            ->where("auditable_id", $childId)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($updateEntry);
        $this->assertContains("first_name", $updateEntry->changed_fields ?? []);
        $this->assertContains("gender", $updateEntry->changed_fields ?? []);
        $this->assertSame("Лог", $updateEntry->before["first_name"] ?? null);
        $this->assertSame("Обновленный", $updateEntry->after["first_name"] ?? null);
        $this->assertSame("male", $updateEntry->before["gender"] ?? null);
        $this->assertSame("female", $updateEntry->after["gender"] ?? null);

        $this->withHeaders($auth["headers"])
            ->deleteJson($this->endpoint() . "/{$childId}")
            ->assertOk();

        $deleteEntry = ChangeLog::query()
            ->where("auditable_type", Child::class)
            ->where("auditable_id", $childId)
            ->where("event", "delete")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($deleteEntry);
        $this->assertSame("Обновленный", $deleteEntry->before["first_name"] ?? null);
    }
}
