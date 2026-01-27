<?php

declare(strict_types=1);

namespace Modules\Users\Validation;

final class EmailRules
{
    public static function requiredUnique(?string $ignoreUserId = null): array
    {
        return self::build(
            presenceRule: 'required',
            ignoreUserId: $ignoreUserId
        );
    }

    public static function sometimesUnique(?string $ignoreUserId = null): array
    {
        return self::build(
            presenceRule: 'sometimes',
            ignoreUserId: $ignoreUserId
        );
    }

    private static function build(
        string $presenceRule,
        ?string $ignoreUserId = null
    ): array {
        $uniqueRule = 'unique:users,email';

        if ($ignoreUserId !== null) {
            $uniqueRule .= ',' . $ignoreUserId;
        }

        return [
            $presenceRule,
            'email',
            $uniqueRule,
        ];
    }
}
