<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use App\Shared\Services\ObservabilityService;
use Tests\TestCase;
use Modules\Auth\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Modules\Auth\Contracts\TokenServiceInterface;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Services\PresenceService;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function register_creates_user_and_returns_token(): void
    {
        $data = [
            "email" => "test@example.com",
            "password" => "password123",
            "first_name" => "Test",
            "last_name" => "User",
        ];

        $user = new User([
            "email" => "test@example.com",
            "first_name" => "Test",
            "last_name" => "User",
        ]);

        $usersService = Mockery::mock(UsersServiceInterface::class);
        $usersService->shouldReceive("createUser")->once()->with($data)->andReturn($user);

        $tokenService = Mockery::mock(TokenServiceInterface::class);
        $tokenService->shouldReceive("createToken")->once()->with($user)->andReturn("fake-token");

        Redis::shouldReceive("connection")->never();

        $presenceService = app(PresenceService::class);
        $observabilityService = app(ObservabilityService::class);

        $authService = new AuthService(
            $usersService,
            $tokenService,
            $presenceService,
            $observabilityService,
        );

        $result = $authService->register($data);

        $this->assertSame("fake-token", $result["token"]);
    }

    #[Test]
    public function login_returns_user_and_token(): void
    {
        $credentials = [
            "email" => "test@example.com",
            "password" => "password123",
        ];

        $user = User::factory()->create([
            "email" => "test@example.com",
            "first_name" => "Test",
            "last_name" => "User",
            "password" => "password123",
        ]);

        $usersService = Mockery::mock(UsersServiceInterface::class);
        $usersService
            ->shouldReceive("findByEmail")
            ->once()
            ->with($credentials["email"])
            ->andReturn($user);

        $tokenService = Mockery::mock(TokenServiceInterface::class);
        $tokenService->shouldReceive("createToken")->once()->with($user)->andReturn("fake-token");

        $key = sprintf(
            "%s%s%s",
            (string) config("presence.key_prefix", "presence:user:"),
            $user->id,
            (string) config("presence.key_suffix", ":online"),
        );

        Redis::shouldReceive("connection")
            ->once()
            ->with((string) config("presence.redis_connection", "presence"))
            ->andReturnSelf();
        Redis::shouldReceive("setex")->once()->with($key, 90, 1)->andReturn(1);

        $presenceService = app(PresenceService::class);
        $observabilityService = app(ObservabilityService::class);

        $authService = new AuthService(
            $usersService,
            $tokenService,
            $presenceService,
            $observabilityService,
        );

        $result = $authService->login($credentials);

        $this->assertSame("fake-token", $result["token"]);
    }
}
