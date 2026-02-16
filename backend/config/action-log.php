<?php

declare(strict_types=1);

return [
    "enabled" => env("ACTION_LOG_ENABLED", true),
    "exclude" => ["created_at", "updated_at", "settings"],
    "models" => [
        "user" => \Modules\Users\Models\User::class,
        "role" => \Modules\Users\Models\Role::class,
        "child" => \Modules\Children\Models\Child::class,
    ],
];
