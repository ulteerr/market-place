<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateMeProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_update_profile_and_email(): void
    {
        $auth = $this->actingAsUser();

        $response = $this
            ->withHeaders($auth['headers'])
            ->patchJson('/api/me', [
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'middle_name' => 'Иванович',
                'email' => 'new-email@example.com',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.first_name', 'Иван')
            ->assertJsonPath('user.last_name', 'Петров')
            ->assertJsonPath('user.middle_name', 'Иванович')
            ->assertJsonPath('user.email', 'new-email@example.com');

        $auth['user']->refresh();

        $this->assertSame('Иван', $auth['user']->first_name);
        $this->assertSame('Петров', $auth['user']->last_name);
        $this->assertSame('Иванович', $auth['user']->middle_name);
        $this->assertSame('new-email@example.com', $auth['user']->email);
    }

    #[Test]
    public function email_must_be_unique_when_updating_profile(): void
    {
        $auth = $this->actingAsUser();
        User::factory()->create(['email' => 'taken@example.com']);

        $this
            ->withHeaders($auth['headers'])
            ->patchJson('/api/me', [
                'email' => 'taken@example.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function guest_cannot_update_profile(): void
    {
        $this
            ->patchJson('/api/me', [
                'first_name' => 'Guest',
                'email' => 'guest@example.com',
            ])
            ->assertUnauthorized();
    }
}
