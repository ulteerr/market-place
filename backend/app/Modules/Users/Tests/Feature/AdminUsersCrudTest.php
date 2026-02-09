<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;

final class AdminUsersCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return "/api/admin/users";
    }

    protected function table(): string
    {
        return "users";
    }

    protected function seedForList(int $count): void
    {
        User::factory()->count($count)->create();
    }

    protected function seedForCreate(): void
    {
        Role::factory()->participant()->create();
        Role::factory()->moderator()->create();
    }

    protected function createPayload(): array
    {
        return [
            "first_name" => "John",
            "last_name" => "Doe",
            "email" => "john@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
            "roles" => ["moderator"],
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            "email" => "john@example.com",
            "first_name" => "John",
            "last_name" => "Doe",
        ];
    }

    protected function createItem(): mixed
    {
        Role::factory()->participant()->create();

        return User::factory()->create([
            "first_name" => "Before",
            "last_name" => "User",
        ]);
    }

    protected function itemId(mixed $item): string
    {
        return (string) $item->id;
    }

    protected function showIdPath(): string
    {
        return "user.id";
    }

    protected function updatePayload(mixed $item): array
    {
        return [
            "first_name" => "After",
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            "id" => $item->id,
            "first_name" => "After",
        ];
    }

    protected function afterCreateAssertions(): void
    {
        $user = User::where("email", "john@example.com")->firstOrFail();

        $this->assertSame(
            ["moderator", "participant"],
            $user->roles()->pluck("code")->sort()->values()->all(),
        );
    }

    protected function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }

    #[Test]
    public function admin_can_search_users_by_name_and_contacts_and_roles(): void
    {
        $auth = $this->actingAsAdmin();

        User::factory()->create([
            "first_name" => "Иван",
            "last_name" => "Петров",
            "middle_name" => "Иванович",
            "email" => "ivan.petrov@example.com",
            "phone" => "+79990000001",
        ]);

        $secondUser = User::factory()->create([
            "first_name" => "Сергей",
            "last_name" => "Иванов",
            "middle_name" => "Сергеевич",
            "email" => "sergey@example.com",
            "phone" => "+79990000002",
        ]);

        $moderatorRole = Role::factory()->moderator()->create();
        $secondUser->roles()->sync([$moderatorRole->id]);

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=Иванович")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.first_name", "Иван");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=sergey@example.com")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.email", "sergey@example.com");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=+79990000002")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.phone", "+79990000002");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=moderator")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.email", "sergey@example.com");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=Иван Петров")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.email", "ivan.petrov@example.com");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=Петров Иванович")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.email", "ivan.petrov@example.com");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=Иванович Иван")
            ->assertOk()
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.email", "ivan.petrov@example.com");
    }

    #[Test]
    public function admin_can_sort_users_by_each_name_field(): void
    {
        $auth = $this->actingAsAdmin();

        User::factory()->create([
            "first_name" => "Борис",
            "last_name" => "Васильев",
            "middle_name" => "SortCaseB",
        ]);
        User::factory()->create([
            "first_name" => "Алексей",
            "last_name" => "Сидоров",
            "middle_name" => "SortCaseA",
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=SortCase&sort_by=first_name&sort_dir=asc")
            ->assertOk()
            ->assertJsonCount(2, "data.data")
            ->assertJsonPath("data.data.0.first_name", "Алексей");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=SortCase&sort_by=last_name&sort_dir=asc")
            ->assertOk()
            ->assertJsonCount(2, "data.data")
            ->assertJsonPath("data.data.0.last_name", "Васильев");

        $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "?search=SortCase&sort_by=middle_name&sort_dir=asc")
            ->assertOk()
            ->assertJsonCount(2, "data.data")
            ->assertJsonPath("data.data.0.middle_name", "SortCaseA");
    }
}
