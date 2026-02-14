<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Validation\UserProfileRules;
use Modules\Users\Validation\PasswordRules;
use Modules\Users\Validation\EmailRules;
use Modules\Users\Validation\RolesRules;

final class UpdateAdminUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->has("avatar_delete")) {
            return;
        }

        $value = $this->input("avatar_delete");
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ["true", "false"], true)) {
                $this->merge([
                    "avatar_delete" => $normalized === "true",
                ]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (string) $this->route("id");

        return array_merge(
            [
                "email" => EmailRules::sometimesUnique($userId),
                "password" => array_merge(["sometimes"], PasswordRules::default()),
                "avatar" => ["sometimes", "file", "image", "mimes:jpg,jpeg,png,webp", "max:5120"],
                "avatar_delete" => ["sometimes", "boolean"],
            ],
            UserProfileRules::base(),
            RolesRules::optional(),
        );
    }
}
