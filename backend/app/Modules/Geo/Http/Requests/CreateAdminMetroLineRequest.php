<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdminMetroLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "external_id" => ["nullable", "string", "max:255"],
            "line_id" => ["nullable", "string", "max:255"],
            "color" => ["nullable", "string", "max:32"],
            "city_id" => ["required", "uuid", "exists:cities,id"],
            "source" => ["required", "string", "max:255"],
        ];
    }
}
