<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;


class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_login_with_valid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson(fn ($json) =>
                $json
                    ->where('status', 'ok')
                    ->has('user', fn ($json) =>
                        $json
                            ->has('id')
                            ->has('email')
                            ->has('first_name')
                            ->has('last_name')
                    )
                    ->has('token')
            );
    }

    #[Test]
    public function login_fails_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
