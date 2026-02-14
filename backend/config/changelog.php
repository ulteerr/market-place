<?php

declare(strict_types=1);

use Modules\Users\Models\Role;
use Modules\Users\Models\User;

return [
    "models" => [
        "profile" => User::class,
        "user" => User::class,
        "role" => Role::class,
    ],
    "exclude" => ["created_at", "updated_at", "remember_token"],
    "admin" => [
        "list_mode" => env("CHANGELOG_ADMIN_LIST_MODE", "latest"),
        "latest_limit" => (int) env("CHANGELOG_ADMIN_LATEST_LIMIT", 20),
        "default_per_page" => (int) env("CHANGELOG_ADMIN_PER_PAGE", 30),
        "max_per_page" => (int) env("CHANGELOG_ADMIN_MAX_PER_PAGE", 200),
    ],
];
