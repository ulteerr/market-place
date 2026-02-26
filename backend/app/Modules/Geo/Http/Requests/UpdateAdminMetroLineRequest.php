<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminMetroLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "external_id" => ["sometimes", "nullable", "string", "max:255"],
            "line_id" => ["sometimes", "nullable", "string", "max:255"],
            "color" => ["sometimes", "nullable", "string", "max:32"],
            "city_id" => ["sometimes", "uuid", "exists:cities,id"],
            "source" => ["sometimes", "string", "max:255"],
        ];
    }
}
