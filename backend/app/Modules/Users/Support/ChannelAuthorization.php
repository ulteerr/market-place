<?php

declare(strict_types=1);

namespace Modules\Users\Support;

final class ChannelAuthorization
{
    public static function canAccessOwnUserChannel(mixed $user, string $id): bool
    {
        $userId = is_object($user) && isset($user->id) ? (string) $user->id : "";

        return $userId !== "" && $userId === $id;
    }
}
