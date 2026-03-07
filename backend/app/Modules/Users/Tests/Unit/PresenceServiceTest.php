<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Models\User;
use Modules\Users\Services\PresenceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PresenceServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function is_online_map_reads_presence_in_single_batch_call(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $onlineUser = User::factory()->create();
        $offlineUser = User::factory()->create();
        $onlineUser->markLastSeen(Carbon::parse("2026-03-06 11:40:00"));
        $offlineUser->markLastSeen(Carbon::parse("2026-03-06 11:59:30"));

        $keyPrefix = (string) config("presence.redis_connection", "presence");

        $redisConnection = \Mockery::mock();
        $redisConnection
            ->shouldReceive("mget")
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function (mixed ...$args) use ($onlineUser, $offlineUser): array {
                $keys = $args;
                if (count($args) === 1 && is_array($args[0])) {
                    $keys = $args[0];
                }

                return array_map(function (string $key) use ($onlineUser, $offlineUser): ?string {
                    if (str_contains($key, (string) $onlineUser->id)) {
                        return "1";
                    }

                    if (str_contains($key, (string) $offlineUser->id)) {
                        return null;
                    }

                    return null;
                }, $keys);
            });
        $redisConnection->shouldNotReceive("exists");

        Redis::shouldReceive("connection")->once()->with($keyPrefix)->andReturn($redisConnection);

        $result = app(PresenceService::class)->isOnlineMap([$onlineUser, $offlineUser]);

        $this->assertArrayHasKey((string) $onlineUser->id, $result);
        $this->assertArrayHasKey((string) $offlineUser->id, $result);
        $this->assertSame(true, $result[(string) $onlineUser->id]);
        $this->assertSame(false, $result[(string) $offlineUser->id]);
    }

    #[Test]
    public function is_online_map_falls_back_to_last_seen_when_redis_fails(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));

        $recentUser = User::factory()->create();
        $staleUser = User::factory()->create();
        $recentUser->markLastSeen(Carbon::parse("2026-03-06 11:59:30"));
        $staleUser->markLastSeen(Carbon::parse("2026-03-06 11:40:00"));

        $keyPrefix = (string) config("presence.redis_connection", "presence");
        Redis::shouldReceive("connection")
            ->once()
            ->with($keyPrefix)
            ->andThrow(new \RuntimeException("redis unavailable"));

        $result = app(PresenceService::class)->isOnlineMap([$recentUser, $staleUser]);

        $this->assertSame(true, $result[(string) $recentUser->id] ?? null);
        $this->assertSame(false, $result[(string) $staleUser->id] ?? null);
    }
}
