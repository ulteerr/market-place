<?php

declare(strict_types=1);

namespace Modules\Users\Validation;

final class PasswordRules
{
    /**
     * Default password rules.
     *
     * @param bool $confirmed
     */
    public static function default(bool $confirmed = true): array
    {
        $rules = ['string', 'min:6'];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
