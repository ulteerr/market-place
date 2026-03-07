<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Events\UserWentOffline;
use Modules\Users\Events\UserWentOnline;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LoginLogoutPresenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_updates_last_seen_and_marks_user_online(): void
    {
        Event::fake([UserWentOnline::class]);
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $user = User::factory()->create([
            "email" => "test@example.com",
            "password" => "password123",
            "first_name" => "Ivan",
        ]);
        $user->markLastSeen(Carbon::parse("2026-03-06 11:00:00"));
        $key = $this->presenceKey($user);
        $ttl = (int) config("presence.online_ttl_seconds", 90);

        $redisConnection = \Mockery::mock();
        $redisConnection->shouldReceive("setex")->once()->with($key, $ttl, 1)->andReturn(1);
        Redis::shouldReceive("connection")
            ->once()
            ->with((string) config("presence.redis_connection", "presence"))
            ->andReturn($redisConnection);
        $redisConnection->shouldNotReceive("del");

        $this->postJson("/api/auth/login", [
            "email" => "test@example.com",
            "password" => "password123",
        ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("user.id", $user->id);

        $user->refresh();
        $this->assertTrue(
            $user->last_seen_at?->equalTo(Carbon::parse("2026-03-06 12:00:00")) ?? false,
        );
        Event::assertDispatched(
            UserWentOnline::class,
            fn(UserWentOnline $event): bool => (string) $event->user->id === (string) $user->id,
        );
    }

    #[Test]
    public function logout_updates_last_seen_timestamp(): void
    {
        Event::fake([UserWentOffline::class]);
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));

        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];

        $user->markLastSeen(Carbon::parse("2026-03-06 11:00:00"));
        $key = $this->presenceKey($user);

        $redisConnection = \Mockery::mock();
        $redisConnection->shouldReceive("del")->once()->with($key)->andReturn(1);
        Redis::shouldReceive("connection")
            ->once()
            ->with((string) config("presence.redis_connection", "presence"))
            ->andReturn($redisConnection);
        $redisConnection->shouldNotReceive("setex");

        $this->withHeaders($auth["headers"])->postJson("/api/auth/logout")->assertOk();

        $user->refresh();
        $this->assertTrue(
            $user->last_seen_at?->equalTo(Carbon::parse("2026-03-06 12:00:00")) ?? false,
        );
        Event::assertDispatched(
            UserWentOffline::class,
            fn(UserWentOffline $event): bool => (string) $event->user->id === (string) $user->id,
        );
    }

    #[Test]
    public function logout_removes_presence_key_from_isolated_redis_namespace(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));

        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];

        $prefix = sprintf("presence:test:%s:", bin2hex(random_bytes(4)));
        $presentUserKeys = [];
        $keyPrefix = (string) config("presence.redis_connection", "presence");

        $redisConnection = \Mockery::mock();
        $redisConnection
            ->shouldReceive("setex")
            ->andReturnUsing(function (string $key, int $ttl, mixed $value) use (
                &$presentUserKeys,
            ): int {
                $presentUserKeys[$key] = [
                    "value" => $value,
                    "ttl" => $ttl,
                ];

                return 1;
            });
        $redisConnection
            ->shouldReceive("exists")
            ->andReturnUsing(function (string $key) use (&$presentUserKeys): int {
                return array_key_exists($key, $presentUserKeys) ? 1 : 0;
            });
        $redisConnection
            ->shouldReceive("del")
            ->andReturnUsing(function (string $key) use (&$presentUserKeys): int {
                if (!array_key_exists($key, $presentUserKeys)) {
                    return 0;
                }

                unset($presentUserKeys[$key]);

                return 1;
            });
        Redis::shouldReceive("connection")->with($keyPrefix)->andReturn($redisConnection);
        config([
            "presence.key_prefix" => $prefix,
            "presence.key_suffix" => ":online",
        ]);

        $key = $this->presenceKey($user);
        $ttl = (int) config("presence.online_ttl_seconds", 90);

        Redis::connection($keyPrefix)->setex($key, $ttl, 1);
        $this->assertSame(1, (int) Redis::connection($keyPrefix)->exists($key));

        $this->withHeaders($auth["headers"])->postJson("/api/auth/logout")->assertOk();

        $this->assertSame(0, (int) Redis::connection($keyPrefix)->exists($key));
    }

    private function presenceKey(User $user): string
    {
        return sprintf(
            "%s%s%s",
            (string) config("presence.key_prefix", "presence:user:"),
            $user->id,
            (string) config("presence.key_suffix", ":online"),
        );
    }
}
