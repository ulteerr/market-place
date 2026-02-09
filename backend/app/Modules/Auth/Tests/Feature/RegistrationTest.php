<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Database\Seeders\RolesSeeder;
use PHPUnit\Framework\Attributes\Test;


class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register()
    {
        $this->seed(RolesSeeder::class);

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(201)
            ->assertJson(
                fn($json) =>
                $json
                    ->where('status', 'ok')
                    ->has(
                        'user',
                        fn($json) =>
                        $json
                            ->has('id')
                            ->has('email')
                            ->has('first_name')
                            ->has('last_name')
                            ->has('middle_name')
                            ->has('settings')
                            ->has('roles')
                            ->has('is_admin')
                            ->has('can_access_admin_panel')
                            ->etc()
                    )
                    ->has('token')
            );


        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    #[Test]
    public function registration_validation_fails()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'password',
            ]);
    }
}
