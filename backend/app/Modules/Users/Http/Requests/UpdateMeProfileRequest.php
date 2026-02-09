<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Validation\EmailRules;
use Modules\Users\Validation\UserProfileRules;

final class UpdateMeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (string) optional($this->user())->id;

        return array_merge(
            [
                'email' => EmailRules::sometimesUnique($userId !== '' ? $userId : null),
            ],
            UserProfileRules::base()
        );
    }
}
