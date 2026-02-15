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
        if ($this->has("avatar_delete")) {
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

        $this->normalizePermissionOverridesPayload();
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
                "permission_overrides" => ["sometimes", "array"],
                "permission_overrides.allow" => ["sometimes", "array"],
                "permission_overrides.allow.*" => [
                    "string",
                    "distinct",
                    "exists:access_permissions,code",
                ],
                "permission_overrides.deny" => ["sometimes", "array"],
                "permission_overrides.deny.*" => [
                    "string",
                    "distinct",
                    "exists:access_permissions,code",
                ],
            ],
            UserProfileRules::base(),
            RolesRules::optional(),
        );
    }

    private function normalizePermissionOverridesPayload(): void
    {
        $hasExplicitOverrides = $this->boolean("permission_overrides_present");
        $hasOverridesPayload = $this->has("permission_overrides");

        if (!$hasExplicitOverrides && !$hasOverridesPayload) {
            return;
        }

        $rawOverrides = $this->input("permission_overrides", []);
        if (!is_array($rawOverrides)) {
            $rawOverrides = [];
        }

        $allow = $rawOverrides["allow"] ?? $this->input("permission_overrides.allow", []);
        $deny = $rawOverrides["deny"] ?? $this->input("permission_overrides.deny", []);

        $this->merge([
            "permission_overrides" => [
                "allow" => is_array($allow) ? $allow : [],
                "deny" => is_array($deny) ? $deny : [],
            ],
        ]);
    }
}
