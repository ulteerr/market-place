<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Models\User;
use Modules\Users\Services\PresenceService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Throwable;
use Tests\TestCase;

final class PresenceRedisIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[Group("redis")]
    public function redis_presence_key_lifecycle_with_real_redis_works(): void
    {
        if (!extension_loaded("redis")) {
            $this->markTestSkipped(
                "phpredis extension is required for real Redis integration test.",
            );
        }

        $connection = (string) config("presence.redis_connection", "presence");

        try {
            Redis::connection($connection)->ping();
        } catch (Throwable $exception) {
            $this->markTestSkipped("Redis service is not available: " . $exception->getMessage());
        }

        $ttlSeconds = 2;
        $prefix = sprintf("presence:test:%s:", bin2hex(random_bytes(4)));
        $user = User::factory()->create();
        $key = sprintf("%s%s%s", $prefix, $user->id, ":online");

        $user->markLastSeen(now()->subMinutes(5));

        config([
            "presence.online_ttl_seconds" => $ttlSeconds,
            "presence.redis_connection" => $connection,
            "presence.key_prefix" => $prefix,
            "presence.key_suffix" => ":online",
        ]);

        $service = app(PresenceService::class);

        try {
            $service->setOnline($user);

            $this->assertSame(1, (int) Redis::connection($connection)->exists($key));
            $this->assertGreaterThan(0, (int) Redis::connection($connection)->ttl($key));

            sleep(3);

            $this->assertSame(0, (int) Redis::connection($connection)->exists($key));
            $this->assertFalse($service->isOnline($user));
        } finally {
            Redis::connection($connection)->del($key);
            $user->forceDelete();
        }
    }
}
