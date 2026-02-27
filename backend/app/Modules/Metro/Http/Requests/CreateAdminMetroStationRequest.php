<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdminMetroStationRequest extends FormRequest
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
            "geo_lat" => ["nullable", "numeric", "between:-90,90"],
            "geo_lon" => ["nullable", "numeric", "between:-180,180"],
            "is_closed" => ["nullable", "boolean"],
            "metro_line_id" => ["required", "uuid", "exists:metro_lines,id"],
            "city_id" => ["required", "uuid", "exists:cities,id"],
            "source" => ["required", "string", "max:255"],
        ];
    }
}
