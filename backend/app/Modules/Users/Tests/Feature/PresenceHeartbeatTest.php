<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Models\User;
use Modules\Users\Services\PresenceService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class PresenceHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function heartbeat_updates_last_seen_when_ttl_expired(): void
    {
        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];

        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $user->markLastSeen(Carbon::parse("2026-03-06 11:50:00"));
        $this->mockRedisPresenceForUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Heartbeat accepted");

        $this->assertTrue(
            $user->refresh()->last_seen_at?->equalTo(Carbon::parse("2026-03-06 12:00:00")) ?? false,
        );
    }

    #[Test]
    public function heartbeat_skips_last_seen_update_when_ttl_not_expired(): void
    {
        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];

        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $lastSeen = Carbon::parse("2026-03-06 11:55:30");
        $user->markLastSeen($lastSeen);
        $this->mockRedisPresenceForUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Heartbeat accepted");

        $this->assertTrue($user->refresh()->last_seen_at?->equalTo($lastSeen) ?? false);
    }

    #[Test]
    public function heartbeat_is_available_only_for_authorized_users(): void
    {
        $this->postJson("/api/presence/heartbeat")->assertUnauthorized();
    }

    #[Test]
    public function heartbeat_marks_authorized_user_online(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];
        $this->mockRedisPresenceForUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Heartbeat accepted");

        $user = $user->refresh();
        $this->assertTrue(
            $user->last_seen_at?->equalTo(Carbon::parse("2026-03-06 12:00:00")) ?? false,
        );
        $this->assertTrue(app(PresenceService::class)->isOnline($user));
    }

    #[Test]
    public function heartbeat_does_not_fail_when_redis_is_unavailable(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $auth = $this->actingAsUser();
        /** @var User $user */
        $user = $auth["user"];
        $user->markLastSeen(Carbon::parse("2026-03-06 11:00:00"));

        $keyPrefix = (string) config("presence.redis_connection", "presence");
        Redis::shouldReceive("connection")
            ->once()
            ->with($keyPrefix)
            ->andThrow(new RuntimeException("redis unavailable"));

        Log::shouldReceive("warning")
            ->once()
            ->with(
                "PresenceService redis operation failed",
                \Mockery::on(
                    fn(array $context): bool => ($context["method"] ?? null) === "setOnline" &&
                        ($context["connection"] ?? null) === $keyPrefix &&
                        str_contains((string) ($context["error"] ?? ""), "redis unavailable"),
                ),
            );

        $this->withHeaders($auth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Heartbeat accepted");

        $this->assertTrue(
            $user->refresh()->last_seen_at?->equalTo(Carbon::parse("2026-03-06 12:00:00")) ?? false,
        );
    }

    private function mockRedisPresenceForUser(User $user): void
    {
        $keyPrefix = (string) config("presence.redis_connection", "presence");
        $presentUserKeys = [];
        $redisConnection = \Mockery::mock();

        $redisConnection
            ->shouldReceive("setex")
            ->andReturnUsing(function (string $key, int $ttl, mixed $value) use (
                &$presentUserKeys,
            ): int {
                $presentUserKeys[$key] = ["value" => $value, "ttl" => $ttl];

                return 1;
            });

        $redisConnection
            ->shouldReceive("exists")
            ->withAnyArgs()
            ->andReturnUsing(function (string $key) use (&$presentUserKeys): int {
                return array_key_exists($key, $presentUserKeys) ? 1 : 0;
            });

        Redis::shouldReceive("connection")->with($keyPrefix)->andReturn($redisConnection);
    }
}
