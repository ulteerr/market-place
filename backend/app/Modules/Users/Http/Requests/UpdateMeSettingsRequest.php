<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "settings" => ["required", "array"],
            "settings.locale" => ["sometimes", "nullable", "string", Rule::in(["ru", "en"])],
            "settings.theme" => ["sometimes", "string", Rule::in(["light", "dark"])],
            "settings.collapse_menu" => ["sometimes", "boolean"],
            "settings.admin_crud_preferences" => ["sometimes", "array"],
            "settings.admin_crud_preferences.*" => ["array"],
            "settings.admin_crud_preferences.*.contentMode" => [
                "sometimes",
                "string",
                Rule::in(["table", "table-cards", "cards"]),
            ],
            "settings.admin_crud_preferences.*.tableOnDesktop" => ["sometimes", "boolean"],
        ];
    }
}
