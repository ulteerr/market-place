<?php

declare(strict_types=1);

return [
    "redis_connection" => env("PRESENCE_REDIS_CONNECTION", "presence"),
    "key_prefix" => env("PRESENCE_REDIS_KEY_PREFIX", "presence:user:"),
    "key_suffix" => env("PRESENCE_REDIS_KEY_SUFFIX", ":online"),
    "online_ttl_seconds" => (int) env("PRESENCE_REDIS_TTL_SECONDS", 90),
    "heartbeat_seconds" => (int) env("PRESENCE_HEARTBEAT_SECONDS", 30),
    "last_seen_upsert_ttl_seconds" => (int) max(
        1,
        (int) env("LAST_SEEN_TTL_MINUTES_FOR_DB_UPSERT", 5) * 60,
    ),
];
