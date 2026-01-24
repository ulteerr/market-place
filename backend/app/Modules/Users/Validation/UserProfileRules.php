<?php
declare(strict_types=1);

namespace Modules\Users\Validation;

final class UserProfileRules
{
    public static function base(): array
    {
        return [
            'first_name'  => ['string', 'max:255'],
            'last_name'   => ['string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
        ];
    }

    public static function required(): array
    {
        return [
            'first_name' => ['required'],
            'last_name'  => ['required'],
        ];
    }

    public static function password(): array
    {
        return [
            'password' => ['string', 'min:6', 'confirmed'],
        ];
    }
}
