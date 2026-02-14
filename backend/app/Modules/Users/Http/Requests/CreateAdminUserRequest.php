<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Validation\EmailRules;
use Modules\Users\Validation\UserProfileRules;
use Modules\Users\Validation\PasswordRules;
use Modules\Users\Validation\RolesRules;

final class CreateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            [
                "email" => EmailRules::requiredUnique(),
                "password" => array_merge(["required"], PasswordRules::default()),
                "avatar" => ["sometimes", "file", "image", "mimes:jpg,jpeg,png,webp", "max:5120"],
            ],
            UserProfileRules::base(),
            UserProfileRules::required(),
            RolesRules::optional(),
        );
    }
}
