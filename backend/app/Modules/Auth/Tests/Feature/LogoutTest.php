<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;


class LogoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function guest_cannot_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}
