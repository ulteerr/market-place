<?php

declare(strict_types=1);

return [
    "enabled" => env("OBSERVABILITY_ENABLED", true),
    "cache_store" => env("OBSERVABILITY_CACHE_STORE"),
    "summary_key" => env("OBSERVABILITY_SUMMARY_KEY", "observability:summary"),
    "incidents_key" => env("OBSERVABILITY_INCIDENTS_KEY", "observability:incidents"),
    "incidents_limit" => (int) env("OBSERVABILITY_INCIDENTS_LIMIT", 200),
    "log_channel" => env("OBSERVABILITY_LOG_CHANNEL", "stack"),
    "alerts_enabled" => env("OBSERVABILITY_ALERTS_ENABLED", true),
    "alerts_min_events" => (int) env("OBSERVABILITY_ALERTS_MIN_EVENTS", 20),
    "alerts_error_rate_threshold" => (float) env("OBSERVABILITY_ALERTS_ERROR_RATE_THRESHOLD", 0.2),
    "masked_value" => "[masked]",
    "sensitive_keys" => [
        "password",
        "token",
        "authorization",
        "access_token",
        "refresh_token",
        "secret",
        "api_key",
        "email",
        "phone",
    ],
];
