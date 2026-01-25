<?php
declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

final class UpdateMePasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_change_password(): void
    {
        
        $auth = $this->actingAsUser();

      
        $user = $auth['user'];

        
        $user->update([
            'password' => bcrypt('old-password'),
        ]);

        
        $response = $this
            ->withHeaders($auth['headers'])
            ->patchJson('/api/me/password', [
                'password'              => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        
        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'user'   => [
                    'id'    => $user->id,
                    'email' => $user->email,
                ],
            ]);

        $user->refresh();

        $this->assertTrue(
            Hash::check('new-password', $user->password),
            'Password was not updated or not hashed correctly'
        );
    }

    #[Test]
    public function password_confirmation_must_match(): void
    {
        
        $auth = $this->actingAsUser();

      
        $response = $this
            ->withHeaders($auth['headers'])
            ->patchJson('/api/me/password', [
                'password'              => 'new-password',
                'password_confirmation' => 'wrong-confirmation',
            ]);

   
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function guest_cannot_change_password(): void
    {
        $response = $this->patchJson('/api/me/password', [
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertUnauthorized();
    }
}
