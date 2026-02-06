<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

final class AdminUsersCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return '/api/admin/users';
    }

    protected function table(): string
    {
        return 'users';
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
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['moderator'],
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];
    }

    protected function createItem(): mixed
    {
        Role::factory()->participant()->create();

        return User::factory()->create([
            'first_name' => 'Before',
            'last_name' => 'User',
        ]);
    }

    protected function itemId(mixed $item): string
    {
        return (string) $item->id;
    }

    protected function showIdPath(): string
    {
        return 'user.id';
    }

    protected function updatePayload(mixed $item): array
    {
        return [
            'first_name' => 'After',
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            'id' => $item->id,
            'first_name' => 'After',
        ];
    }

    protected function afterCreateAssertions(): void
    {
        $user = User::where('email', 'john@example.com')->firstOrFail();

        $this->assertSame(
            ['moderator', 'participant'],
            $user->roles()->pluck('code')->sort()->values()->all()
        );
    }

    protected function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth['user']->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
