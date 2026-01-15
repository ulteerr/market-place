<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;

final class MeTest extends TestCase
{
	use RefreshDatabase;

	#[Test]
	public function test_authenticated_user_can_get_profile(): void
	{
		$user = User::factory()->create();

		$token = $user->createToken('test-token')->plainTextToken;

		$response = $this
			->withHeader('Authorization', 'Bearer ' . $token)
			->getJson('/api/me');

		$response
			->assertOk()
			->assertJson([
				'status' => 'ok',
				'user' => [
					'id' => $user->id,
					'email' => $user->email,
				],
			]);
	}

	#[Test]
	public function test_guest_cannot_get_profile(): void
	{
		$this
			->getJson('/api/me')
			->assertUnauthorized();
	}
}
