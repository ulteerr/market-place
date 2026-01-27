<?php

declare(strict_types=1);

namespace Modules\Users\Validation;

final class RolesRules
{
    public static function optional(): array
    {
        return [
            'roles'   => ['nullable', 'array'],
            'roles.*' => ['string'],
        ];
    }
}
