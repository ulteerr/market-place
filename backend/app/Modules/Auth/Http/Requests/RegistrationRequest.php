<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Validation\EmailRules;
use Modules\Users\Validation\PasswordRules;
use Modules\Users\Validation\UserProfileRules;

final class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            [
                'email'    => EmailRules::requiredUnique(),
                'password' => array_merge(
                    ['required'],
                    PasswordRules::default()
                ),
            ],
            UserProfileRules::base(),
            UserProfileRules::required()
        );
    }
}
