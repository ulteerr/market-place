<?php

declare(strict_types=1);

namespace Modules\Users\Console\Commands;

use App\Shared\Services\ObservabilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Users\Events\UserWentOffline;
use Modules\Users\Models\User;
use Redis as PhpRedis;
use Throwable;

final class WatchPresenceExpiryCommand extends Command
{
    protected $signature = "presence:watch-offline";
    protected $description = "Watch Redis presence key expiration and broadcast offline events";

    public function __construct(private readonly ObservabilityService $observabilityService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $connection = (string) config("presence.redis_connection", "presence");
        $redisConfig = (array) config("database.redis.{$connection}", []);
        $database = (int) config("database.redis.{$connection}.database", 2);
        $pattern = sprintf("__keyevent@%d__:expired", $database);

        $this->info(
            sprintf(
                "Watching Redis expired events on [%s], db=%d, pattern=%s",
                $connection,
                $database,
                $pattern,
            ),
        );

        while (true) {
            try {
                $client = $this->createPubSubClient($redisConfig, $database);
                $client->psubscribe([$pattern], function (
                    PhpRedis $redis,
                    string $matchedPattern,
                    string $channel,
                    string $message,
                ): void {
                    $this->handleExpiredPresenceKey($message, $channel);
                });
            } catch (Throwable $exception) {
                Log::warning("Presence watcher subscription failed", [
                    "connection" => $connection,
                    "pattern" => $pattern,
                    "error" => $exception->getMessage(),
                ]);

                $this->observabilityService->recordEvent(
                    "presence",
                    "presence.watcher",
                    "subscription_error",
                    "error",
                    "warning",
                    null,
                    [
                        "connection" => $connection,
                        "pattern" => $pattern,
                        "error" => $exception->getMessage(),
                    ],
                );

                sleep(1);
            }
        }
    }

    /**
     * @param array<string, mixed> $redisConfig
     */
    private function createPubSubClient(array $redisConfig, int $database): PhpRedis
    {
        $host = (string) ($redisConfig["host"] ?? "127.0.0.1");
        $port = (int) ($redisConfig["port"] ?? 6379);
        $timeout = (float) ($redisConfig["timeout"] ?? 0.0);
        $readTimeout = (float) ($redisConfig["read_timeout"] ?? -1);
        $password = $redisConfig["password"] ?? null;
        $username = $redisConfig["username"] ?? null;

        $client = new PhpRedis();
        $client->connect($host, $port, $timeout);

        if (is_string($password) && $password !== "" && strtolower($password) !== "null") {
            if (is_string($username) && $username !== "") {
                $client->auth([$username, $password]);
            } else {
                $client->auth($password);
            }
        }

        $client->setOption(PhpRedis::OPT_PREFIX, "");
        // Keep Pub/Sub socket open and avoid periodic reconnect noise on idle channels.
        $client->setOption(PhpRedis::OPT_READ_TIMEOUT, $readTimeout);
        $client->select($database);

        return $client;
    }

    private function handleExpiredPresenceKey(string $message, string $channel): void
    {
        $userId = self::extractUserIdFromPresenceKey($message);
        if ($userId === null) {
            return;
        }

        $user = User::query()->find($userId);
        if (!$user instanceof User) {
            return;
        }

        try {
            event(new UserWentOffline($user));
            $this->observabilityService->recordEvent(
                "presence",
                "presence.watcher",
                "offline_expired",
                "ok",
                "info",
                null,
                [
                    "user_id" => (string) $user->id,
                    "channel" => $channel,
                ],
            );
        } catch (Throwable $exception) {
            $this->observabilityService->recordEvent(
                "presence",
                "presence.watcher",
                "offline_expired",
                "error",
                "warning",
                null,
                [
                    "user_id" => (string) $user->id,
                    "channel" => $channel,
                    "error" => $exception->getMessage(),
                ],
            );
        }
    }

    public static function extractUserIdFromPresenceKey(string $key): ?string
    {
        $redisPrefix = (string) config("database.redis.options.prefix", "");
        if ($redisPrefix !== "" && str_starts_with($key, $redisPrefix)) {
            $key = substr($key, strlen($redisPrefix));
            if (!is_string($key)) {
                return null;
            }
        }

        $prefix = (string) config("presence.key_prefix", "presence:user:");
        $suffix = (string) config("presence.key_suffix", ":online");

        if (!str_starts_with($key, $prefix) || !str_ends_with($key, $suffix)) {
            return null;
        }

        $trimmed = substr($key, strlen($prefix));
        if ($trimmed === false) {
            return null;
        }

        $userId = substr($trimmed, 0, -strlen($suffix));
        if (!is_string($userId) || trim($userId) === "") {
            return null;
        }

        return $userId;
    }
}
