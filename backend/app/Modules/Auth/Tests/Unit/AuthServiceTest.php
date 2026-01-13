<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Tests\TestCase;
use Modules\Auth\Services\AuthService;
use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Modules\Auth\Contracts\TokenServiceInterface;
use Illuminate\Support\Facades\Hash;


class AuthServiceTest extends TestCase
{
	protected function tearDown(): void
	{
		Mockery::close();
		parent::tearDown();
	}

	#[Test]
	public function register_creates_user_and_returns_token(): void
	{
		$data = [
			'email' => 'test@example.com',
			'password' => 'password123',
			'first_name' => 'Test',
			'last_name' => 'User',
		];

		$user = new User([
			'email' => 'test@example.com',
			'first_name' => 'Test',
			'last_name' => 'User',
		]);

		$usersService = Mockery::mock(UsersServiceInterface::class);
		$usersService
			->shouldReceive('createUser')
			->once()
			->with($data)
			->andReturn($user);

		$tokenService = Mockery::mock(TokenServiceInterface::class);
		$tokenService
			->shouldReceive('createToken')
			->once()
			->with($user)
			->andReturn('fake-token');

		$authService = new AuthService($usersService, $tokenService);

		$result = $authService->register($data);

		$this->assertSame('fake-token', $result['token']);
	}

	#[Test]
	public function login_returns_user_and_token(): void
	{
		$credentials = [
			'email' => 'test@example.com',
			'password' => 'password123',
		];

		
		$user = new User([
			'email' => 'test@example.com',
			'first_name' => 'Test',
			'last_name' => 'User',
			'password' => Hash::make('password123'),
		]);

		
		$usersService = Mockery::mock(UsersServiceInterface::class);
		$usersService
			->shouldReceive('findByEmail')
			->once()
			->with($credentials['email'])
			->andReturn($user);

		
		$tokenService = Mockery::mock(TokenServiceInterface::class);
		$tokenService
			->shouldReceive('createToken')
			->once()
			->with($user)
			->andReturn('fake-token');

		
		$authService = new AuthService(
			$usersService,
			$tokenService
		);

		
		$result = $authService->login($credentials);

		
		$this->assertSame('fake-token', $result['token']);
	}
}
