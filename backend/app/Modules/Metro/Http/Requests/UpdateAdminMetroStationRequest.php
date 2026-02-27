<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminMetroStationRequest extends FormRequest
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
            "geo_lat" => ["sometimes", "nullable", "numeric", "between:-90,90"],
            "geo_lon" => ["sometimes", "nullable", "numeric", "between:-180,180"],
            "is_closed" => ["sometimes", "nullable", "boolean"],
            "metro_line_id" => ["sometimes", "uuid", "exists:metro_lines,id"],
            "city_id" => ["sometimes", "uuid", "exists:cities,id"],
            "source" => ["sometimes", "string", "max:255"],
        ];
    }
}
