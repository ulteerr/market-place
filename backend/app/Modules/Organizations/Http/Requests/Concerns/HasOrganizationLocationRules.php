<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests\Concerns;

trait HasOrganizationLocationRules
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function organizationLocationRules(string $locationsRule = "nullable"): array
    {
        return [
            "locations" => [$locationsRule, "array"],
            "locations.*.country_id" => ["nullable", "uuid", "exists:countries,id"],
            "locations.*.region_id" => ["nullable", "uuid", "exists:regions,id"],
            "locations.*.city_id" => ["nullable", "uuid", "exists:cities,id"],
            "locations.*.district_id" => ["nullable", "uuid", "exists:districts,id"],
            "locations.*.address" => ["nullable", "string", "max:255"],
            "locations.*.lat" => ["nullable", "numeric", "between:-90,90"],
            "locations.*.lng" => ["nullable", "numeric", "between:-180,180"],
            "locations.*.metro_connections" => ["nullable", "array"],
            "locations.*.metro_connections.*.metro_station_id" => [
                "required",
                "uuid",
                "exists:metro_stations,id",
            ],
            "locations.*.metro_connections.*.travel_mode" => [
                "required",
                "string",
                "in:walk,drive",
            ],
            "locations.*.metro_connections.*.duration_minutes" => [
                "required",
                "integer",
                "min:1",
                "max:1440",
            ],
        ];
    }
}
