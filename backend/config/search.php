<?php

declare(strict_types=1);

return [
    "enabled" => env("SEARCH_ENABLED", true),
    "default" => env("SEARCH_DRIVER", "elasticsearch"),
    "index_prefix" => env("SEARCH_INDEX_PREFIX", "marketplace_"),
    "sql_fallback_enabled" => env("SEARCH_SQL_FALLBACK_ENABLED", true),

    "drivers" => [
        "elasticsearch" => [
            "enabled" => env("ELASTICSEARCH_ENABLED", true),
            "base_url" => env("ELASTICSEARCH_URL", "http://elasticsearch:9200"),
            "timeout_seconds" => (float) env("ELASTICSEARCH_TIMEOUT_SECONDS", 1.5),
            "availability_timeout_seconds" => (float) env(
                "ELASTICSEARCH_AVAILABILITY_TIMEOUT_SECONDS",
                0.35,
            ),
            "verify_tls" => env("ELASTICSEARCH_VERIFY_TLS", false),
            "api_key" => env("ELASTICSEARCH_API_KEY"),
            "username" => env("ELASTICSEARCH_USERNAME"),
            "password" => env("ELASTICSEARCH_PASSWORD"),
        ],
    ],
];
