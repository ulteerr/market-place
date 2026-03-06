<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use App\Shared\Services\ObservabilityService;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Models\User;
use Throwable;

final class PresenceService
{
    private const METHOD_SET = "setOnline";
    private const METHOD_OFFLINE = "setOffline";
    private const METHOD_IS_ONLINE = "isOnline";
    private const METHOD_IS_ONLINE_BATCH = "isOnlineBatch";

    public function __construct(private readonly ObservabilityService $observabilityService) {}

    public function setOnline(User $user): void
    {
        $this->withoutRedisFailure(
            self::METHOD_SET,
            fn(string $connection): int|bool => Redis::connection($connection)->setex(
                $this->presenceKey((string) $user->id),
                $this->ttlSeconds(),
                1,
            ),
        );
    }

    public function setOffline(User $user): void
    {
        $this->withoutRedisFailure(
            self::METHOD_OFFLINE,
            fn(string $connection): int => Redis::connection($connection)->del(
                $this->presenceKey((string) $user->id),
            ),
        );
    }

    public function isOnline(User $user): bool
    {
        $presenceStatus = $this->withoutRedisFailure(
            self::METHOD_IS_ONLINE,
            fn(string $connection): int => Redis::connection($connection)->exists(
                $this->presenceKey((string) $user->id),
            ),
            null,
        );

        if (is_int($presenceStatus)) {
            return $presenceStatus > 0;
        }

        return $this->isOnlineByLastSeen($user);
    }

    /**
     * @param iterable<User> $users
     * @return array<string, bool>
     */
    public function isOnlineMap(iterable $users): array
    {
        $userList = [];
        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $userList[] = $user;
        }

        if ($userList === []) {
            return [];
        }

        $keys = array_map(
            fn(User $user): string => $this->presenceKey((string) $user->id),
            $userList,
        );

        $presenceStatuses = $this->withoutRedisFailure(
            self::METHOD_IS_ONLINE_BATCH,
            fn(string $connection): array => Redis::connection($connection)->mget($keys),
            null,
        );

        $result = [];
        if (is_array($presenceStatuses)) {
            foreach ($userList as $index => $user) {
                $result[(string) $user->id] = $this->isRedisPresenceValueOnline(
                    $presenceStatuses[$index] ?? null,
                );
            }

            return $result;
        }

        foreach ($userList as $user) {
            $result[(string) $user->id] = $this->isOnlineByLastSeen($user);
        }

        return $result;
    }

    public function updateLastSeen(User $user, ?DateTimeInterface $seenAt = null): bool
    {
        $seenAt ??= now();

        if (!$this->shouldRefreshLastSeen($user, $seenAt)) {
            return false;
        }

        $user->markLastSeen($seenAt);

        return true;
    }

    public function heartbeat(User $user): void
    {
        $this->setOnline($user);
        $this->updateLastSeen($user);
    }

    private function shouldRefreshLastSeen(User $user, DateTimeInterface $seenAt): bool
    {
        $lastSeenAt = $user->last_seen_at;
        if (!$lastSeenAt instanceof DateTimeInterface) {
            return true;
        }

        return abs($seenAt->getTimestamp() - $lastSeenAt->getTimestamp()) >=
            $this->lastSeenUpsertTtlSeconds();
    }

    private function isOnlineByLastSeen(User $user): bool
    {
        $lastSeenAt = $user->last_seen_at;
        if (!$lastSeenAt instanceof DateTimeInterface) {
            return false;
        }

        return abs(now()->getTimestamp() - $lastSeenAt->getTimestamp()) <= $this->ttlSeconds();
    }

    private function isRedisPresenceValueOnline(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== "" && $value !== "0";
        }

        return (int) $value > 0;
    }

    private function presenceKey(string $userId): string
    {
        return sprintf(
            "%s%s%s",
            (string) config("presence.key_prefix", "presence:user:"),
            $userId,
            (string) config("presence.key_suffix", ":online"),
        );
    }

    private function ttlSeconds(): int
    {
        return max(1, (int) config("presence.online_ttl_seconds", 90));
    }

    private function lastSeenUpsertTtlSeconds(): int
    {
        return max(1, (int) config("presence.last_seen_upsert_ttl_seconds", 300));
    }

    private function withoutRedisFailure(
        string $method,
        callable $operation,
        bool|int|null $default = null,
    ): bool|int|null {
        try {
            return $operation((string) config("presence.redis_connection", "presence"));
        } catch (Throwable $exception) {
            Log::warning("PresenceService redis operation failed", [
                "method" => $method,
                "connection" => config("presence.redis_connection", "presence"),
                "error" => $exception->getMessage(),
            ]);
            $this->observabilityService->recordEvent(
                "presence",
                "presence.service",
                "redis_operation",
                "error",
                "warning",
                null,
                [
                    "method" => $method,
                    "connection" => (string) config("presence.redis_connection", "presence"),
                    "error" => $exception->getMessage(),
                ],
            );

            return $default;
        }
    }
}
