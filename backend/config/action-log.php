<?php

declare(strict_types=1);

return [
    "enabled" => env("ACTION_LOG_ENABLED", true),
    "models" => [
        "user" => \Modules\Users\Models\User::class,
        "role" => \Modules\Users\Models\Role::class,
    ],
];
