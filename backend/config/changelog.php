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
];
