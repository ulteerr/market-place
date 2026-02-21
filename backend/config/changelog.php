<?php

declare(strict_types=1);

use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\Organization;

return [
    "models" => [
        "profile" => User::class,
        "user" => User::class,
        "role" => Role::class,
        "child" => Child::class,
        "organization" => Organization::class,
        "organizations" => Organization::class,
    ],
    "exclude" => ["created_at", "updated_at", "remember_token", "settings"],
    "admin" => [
        "list_mode" => env("CHANGELOG_ADMIN_LIST_MODE", "latest"),
        "limit" => (int) env("CHANGELOG_ADMIN_LIMIT", 20),
    ],
];
