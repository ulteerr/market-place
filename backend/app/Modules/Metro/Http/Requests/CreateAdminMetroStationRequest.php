<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class CreateAdminMetroStationRequest extends CrudRequest
{
    protected function ruleset(): array
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
