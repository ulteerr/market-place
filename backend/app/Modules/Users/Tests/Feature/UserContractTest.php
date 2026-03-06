<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class UserContractTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_profile_does_not_expose_last_seen_for_current_user(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $auth = $this->actingAsUser();

        /** @var User $user */
        $user = $auth["user"];
        $user->markLastSeen(Carbon::parse("2026-03-06 11:59:00"));

        $response = $this->withHeaders($auth["headers"])->getJson("/api/me");

        $response
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("user.id", $user->id)
            ->assertJsonMissingPath("user.last_seen_at")
            ->assertJsonMissingPath("user.is_online");
    }

    #[Test]
    public function admin_can_view_other_user_last_seen_and_online_status(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $admin = $this->actingAsUser();
        $adminRole = Role::factory()->admin()->create();
        $admin["user"]->roles()->sync([$adminRole->id]);

        $otherUser = User::factory()->create(["first_name" => "Alex"]);
        $lastSeenAt = Carbon::parse("2026-03-06 11:59:30");
        $otherUser->markLastSeen($lastSeenAt);
        $this->mockRedisPresenceForUser($otherUser, true);

        $this->withHeaders($admin["headers"])
            ->getJson("/api/admin/users/{$otherUser->id}")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("user.id", $otherUser->id)
            ->assertJsonPath("user.last_seen_at", $lastSeenAt->toIso8601String())
            ->assertJsonPath("user.is_online", true);
    }

    #[Test]
    public function admin_users_list_includes_presence_fields_for_items(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $admin = $this->actingAsUser();
        $adminRole = Role::factory()->admin()->create();
        $admin["user"]->roles()->sync([$adminRole->id]);

        $onlineUser = User::factory()->create(["first_name" => "PresenceBatchA"]);
        $offlineUser = User::factory()->create(["first_name" => "PresenceBatchB"]);
        $onlineUser->markLastSeen(Carbon::parse("2026-03-06 11:59:30"));
        $offlineUser->markLastSeen(Carbon::parse("2026-03-06 11:40:00"));
        $this->mockRedisPresenceBatch([
            $onlineUser->id => true,
            $offlineUser->id => false,
        ]);

        $response = $this->withHeaders($admin["headers"])
            ->getJson("/api/admin/users?search=PresenceBatch")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.current_page", 1);

        /** @var array<int, array<string, mixed>> $items */
        $items = $response->json("data.data");
        $indexed = collect($items)->keyBy("id");

        $this->assertSame(
            $onlineUser->last_seen_at?->toIso8601String(),
            $indexed->get($onlineUser->id)["last_seen_at"] ?? null,
        );
        $this->assertSame(true, $indexed->get($onlineUser->id)["is_online"] ?? null);

        $this->assertSame(
            $offlineUser->last_seen_at?->toIso8601String(),
            $indexed->get($offlineUser->id)["last_seen_at"] ?? null,
        );
        $this->assertSame(false, $indexed->get($offlineUser->id)["is_online"] ?? null);
    }

    #[Test]
    public function admin_user_show_uses_last_seen_fallback_when_redis_is_unavailable(): void
    {
        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $admin = $this->actingAsUser();
        $adminRole = Role::factory()->admin()->create();
        $admin["user"]->roles()->sync([$adminRole->id]);

        $otherUser = User::factory()->create(["first_name" => "Alex"]);
        $lastSeenAt = Carbon::parse("2026-03-06 11:59:30");
        $otherUser->markLastSeen($lastSeenAt);

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
                    fn(array $context): bool => ($context["method"] ?? null) === "isOnline" &&
                        ($context["connection"] ?? null) === $keyPrefix &&
                        str_contains((string) ($context["error"] ?? ""), "redis unavailable"),
                ),
            );

        $this->withHeaders($admin["headers"])
            ->getJson("/api/admin/users/{$otherUser->id}")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("user.id", $otherUser->id)
            ->assertJsonPath("user.last_seen_at", $lastSeenAt->toIso8601String())
            ->assertJsonPath("user.is_online", true);
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

    private function mockRedisPresenceForUser(User $user, bool $online): void
    {
        $keyPrefix = (string) config("presence.redis_connection", "presence");
        $redisConnection = \Mockery::mock();

        $redisConnection
            ->shouldReceive("exists")
            ->with($this->presenceKey($user))
            ->andReturn($online ? 1 : 0);

        Redis::shouldReceive("connection")->once()->with($keyPrefix)->andReturn($redisConnection);
    }

    /**
     * @param array<string, bool> $onlineByUserId
     */
    private function mockRedisPresenceBatch(array $onlineByUserId): void
    {
        $keyPrefix = (string) config("presence.redis_connection", "presence");

        $redisConnection = \Mockery::mock();
        $redisConnection
            ->shouldReceive("mget")
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(function (mixed ...$args) use ($onlineByUserId): array {
                $keys = $args;
                if (count($args) === 1 && is_array($args[0])) {
                    $keys = $args[0];
                }

                return array_map(function (string $key) use ($onlineByUserId): ?string {
                    foreach ($onlineByUserId as $userId => $isOnline) {
                        if (str_contains($key, (string) $userId)) {
                            return $isOnline ? "1" : null;
                        }
                    }

                    return null;
                }, $keys);
            });

        Redis::shouldReceive("connection")->once()->with($keyPrefix)->andReturn($redisConnection);
    }
}
