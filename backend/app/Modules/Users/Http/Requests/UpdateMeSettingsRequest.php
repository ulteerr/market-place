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
            "settings.favorites" => ["sometimes", "array"],
            "settings.favorites.*" => ["string", "distinct"],
            "settings.public_city" => [
                "sometimes",
                "nullable",
                "array",
                "required_array_keys:city_id,city_name,source,country_id,country_name",
            ],
            "settings.public_city.city_id" => [
                "required_with:settings.public_city",
                "string",
                "uuid",
            ],
            "settings.public_city.city_name" => ["required_with:settings.public_city", "string"],
            "settings.public_city.source" => [
                "required_with:settings.public_city",
                "string",
                Rule::in(["ip_auto", "manual"]),
            ],
            "settings.public_city.region_id" => ["sometimes", "nullable", "string", "uuid"],
            "settings.public_city.region_name" => ["sometimes", "nullable", "string"],
            "settings.public_city.country_id" => [
                "required_with:settings.public_city",
                "string",
                "uuid",
            ],
            "settings.public_city.country_name" => ["required_with:settings.public_city", "string"],
            "settings.admin_crud_preferences" => ["sometimes", "array"],
            "settings.admin_crud_preferences.*" => ["array"],
            "settings.admin_crud_preferences.*.contentMode" => [
                "sometimes",
                "string",
                Rule::in(["table", "table-cards", "cards"]),
            ],
            "settings.admin_crud_preferences.*.tableOnDesktop" => ["sometimes", "boolean"],
            "settings.admin_navigation_sections" => ["sometimes", "array"],
            "settings.admin_navigation_sections.*" => ["array"],
            "settings.admin_navigation_sections.*.open" => ["sometimes", "boolean"],
        ];
    }
}
