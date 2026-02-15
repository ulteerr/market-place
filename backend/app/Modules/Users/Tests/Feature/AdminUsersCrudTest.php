<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Models\AccessPermission;
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

    #[Test]
    public function admin_can_create_user_with_avatar(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();

        $this->withHeaders($auth["headers"])
            ->post($this->endpoint(), [
                "first_name" => "Avatar",
                "last_name" => "Create",
                "email" => "avatar.create@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "avatar" => $this->fakePng("create-avatar.png"),
            ])
            ->assertCreated();

        $user = User::where("email", "avatar.create@example.com")->firstOrFail();
        $this->assertDatabaseHas("files", [
            "fileable_id" => (string) $user->id,
            "fileable_type" => User::class,
            "collection" => "avatar",
            "original_name" => "create-avatar.png",
        ]);
    }

    #[Test]
    public function admin_can_update_user_avatar(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();
        $user = User::factory()->create([
            "email" => "avatar.update@example.com",
        ]);

        $this->withHeaders($auth["headers"])
            ->patch($this->endpoint() . "/" . $user->id, [
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "avatar" => $this->fakePng("update-avatar.png"),
            ])
            ->assertOk();

        $this->assertDatabaseHas("files", [
            "fileable_id" => (string) $user->id,
            "fileable_type" => User::class,
            "collection" => "avatar",
            "original_name" => "update-avatar.png",
        ]);
    }

    #[Test]
    public function admin_can_update_user_profile_roles_and_avatar_in_single_request(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsAdmin();
        $participantRole = Role::factory()->participant()->create();
        $moderatorRole = Role::factory()->moderator()->create();

        $user = User::factory()->create([
            "email" => "avatar.profile.update@example.com",
            "first_name" => "Before",
            "last_name" => "User",
        ]);
        $user->roles()->sync([$participantRole->id]);

        $this->withHeaders($auth["headers"])
            ->patch($this->endpoint() . "/" . $user->id, [
                "first_name" => "After",
                "last_name" => "Updated",
                "email" => $user->email,
                "roles" => ["participant", "moderator"],
                "avatar" => $this->fakePng("update-profile-avatar.png"),
            ])
            ->assertOk();

        $this->assertDatabaseHas("users", [
            "id" => (string) $user->id,
            "first_name" => "After",
            "last_name" => "Updated",
            "email" => $user->email,
        ]);

        $this->assertSame(
            ["moderator", "participant"],
            $user->fresh()->roles()->pluck("code")->sort()->values()->all(),
        );

        $this->assertDatabaseHas("files", [
            "fileable_id" => (string) $user->id,
            "fileable_type" => User::class,
            "collection" => "avatar",
            "original_name" => "update-profile-avatar.png",
        ]);
    }

    #[Test]
    public function admin_can_delete_user_avatar_via_update_payload(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();
        $user = User::factory()->create([
            "email" => "avatar.delete@example.com",
        ]);

        $this->withHeaders($auth["headers"])
            ->patch($this->endpoint() . "/" . $user->id, [
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "avatar" => $this->fakePng("delete-avatar.png"),
            ])
            ->assertOk();

        $this->assertDatabaseHas("files", [
            "fileable_id" => (string) $user->id,
            "fileable_type" => User::class,
            "collection" => "avatar",
            "original_name" => "delete-avatar.png",
        ]);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/" . $user->id, [
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "avatar_delete" => true,
            ])
            ->assertOk();

        $this->assertDatabaseMissing("files", [
            "fileable_id" => (string) $user->id,
            "fileable_type" => User::class,
            "collection" => "avatar",
        ]);
    }

    #[Test]
    public function admin_can_replace_existing_avatar_while_updating_profile_and_roles(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsAdmin();
        $participantRole = Role::factory()->participant()->create();
        $moderatorRole = Role::factory()->moderator()->create();

        $user = User::factory()->create([
            "email" => "avatar.replace@example.com",
            "first_name" => "Before",
            "last_name" => "Avatar",
        ]);
        $user->roles()->sync([$participantRole->id]);

        $this->withHeaders($auth["headers"])
            ->patch($this->endpoint() . "/" . $user->id, [
                "first_name" => "Before",
                "last_name" => "Avatar",
                "email" => $user->email,
                "roles" => ["participant"],
                "avatar" => $this->fakePng("first-avatar.png"),
            ])
            ->assertOk();

        $firstAvatarId = (string) $user->fresh()->avatar?->id;
        $this->assertNotSame("", $firstAvatarId);

        $this->withHeaders($auth["headers"])
            ->patch($this->endpoint() . "/" . $user->id, [
                "first_name" => "After",
                "last_name" => "Updated",
                "email" => $user->email,
                "roles" => ["participant", "moderator"],
                "avatar" => $this->fakePng("second-avatar.png"),
            ])
            ->assertOk();

        $freshUser = $user->fresh();
        $this->assertDatabaseHas("users", [
            "id" => (string) $freshUser->id,
            "first_name" => "After",
            "last_name" => "Updated",
        ]);
        $this->assertSame(
            ["moderator", "participant"],
            $freshUser->roles()->pluck("code")->sort()->values()->all(),
        );

        $this->assertDatabaseHas("files", [
            "fileable_id" => (string) $freshUser->id,
            "fileable_type" => User::class,
            "collection" => "avatar",
            "original_name" => "second-avatar.png",
        ]);
        $this->assertDatabaseHas("files", [
            "id" => $firstAvatarId,
            "fileable_id" => null,
            "fileable_type" => null,
        ]);
    }

    #[Test]
    public function admin_cannot_assign_super_admin_role_on_create(): void
    {
        $auth = $this->actingAsAdmin();
        Role::factory()->participant()->create();
        Role::factory()->superAdmin()->create();

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "first_name" => "No",
                "last_name" => "PrivilegeEscalation",
                "email" => "no-super-admin-create@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["super_admin"],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function admin_cannot_assign_super_admin_role_on_update(): void
    {
        $auth = $this->actingAsAdmin();
        $participantRole = Role::factory()->participant()->create();
        Role::factory()->superAdmin()->create();
        $target = User::factory()->create([
            "email" => "no-super-admin-update@example.com",
        ]);
        $target->roles()->sync([$participantRole->id]);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/" . $target->id, [
                "roles" => ["super_admin"],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_set_and_update_user_permission_overrides(): void
    {
        $auth = $this->actingAsAdmin();
        $participantRole = Role::factory()->participant()->create();
        AccessPermission::query()->create([
            "code" => "admin.users.read",
            "scope" => "admin",
            "label" => "Read users",
        ]);
        AccessPermission::query()->create([
            "code" => "admin.roles.delete",
            "scope" => "admin",
            "label" => "Delete roles",
        ]);

        $createResponse = $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "first_name" => "Override",
                "last_name" => "Target",
                "email" => "override-user@example.com",
                "password" => "password123",
                "password_confirmation" => "password123",
                "roles" => ["participant"],
                "permission_overrides" => [
                    "allow" => ["admin.users.read"],
                    "deny" => ["admin.roles.delete"],
                ],
            ])
            ->assertCreated();

        $userId = (string) $createResponse->json("data.id");
        $user = User::query()->findOrFail($userId);
        $user->roles()->syncWithoutDetaching([$participantRole->id]);

        $this->assertDatabaseCount("user_access_permissions", 2);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/" . $userId, [
                "permission_overrides" => [
                    "allow" => ["admin.users.read"],
                    "deny" => [],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseCount("user_access_permissions", 1);

        $showResponse = $this->withHeaders($auth["headers"])
            ->getJson($this->endpoint() . "/" . $userId)
            ->assertOk();

        $this->assertSame(
            ["admin.users.read"],
            $showResponse->json("user.permission_overrides.allow"),
        );
        $this->assertSame([], $showResponse->json("user.permission_overrides.deny"));
    }

    private function fakePng(string $name = "avatar.png"): UploadedFile
    {
        $pngBinary = base64_decode(
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO6W1k8AAAAASUVORK5CYII=",
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $pngBinary ?: "");
    }
}
