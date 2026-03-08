<?php

declare(strict_types=1);

use App\Shared\Services\ObservabilityService;
use Illuminate\Support\Facades\Broadcast;
use Modules\Users\Support\ChannelAuthorization;

Broadcast::channel("App.Models.User.{id}", function ($user, string $id): bool {
    return ChannelAuthorization::canAccessOwnUserChannel($user, $id);
});

Broadcast::channel("me-settings.{id}", function ($user, string $id): bool {
    return ChannelAuthorization::canAccessOwnUserChannel($user, $id);
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
