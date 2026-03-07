<?php

declare(strict_types=1);

use App\Shared\Services\ObservabilityService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel("App.Models.User.{id}", function ($user, string $id): bool {
    return (string) $user->id === $id;
});

Broadcast::channel("users.presence", function ($user): bool {
    if ($user === null) {
        return false;
    }

    app(ObservabilityService::class)->recordEvent(
        "realtime",
        "realtime.channel",
        "websocket_subscribe_ok",
        "ok",
        "info",
        null,
        [
            "channel" => "users.presence",
            "user_id" => (string) ($user->id ?? ""),
        ],
    );

    return true;
});
