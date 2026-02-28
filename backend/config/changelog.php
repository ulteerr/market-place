<?php

declare(strict_types=1);

use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Children\Models\Child;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Models\MetroStation;
use Modules\Organizations\Models\Organization;

return [
    "models" => [
        "profile" => User::class,
        "user" => User::class,
        "role" => Role::class,
        "child" => Child::class,
        "organization" => Organization::class,
        "organizations" => Organization::class,
        "metro_line" => MetroLine::class,
        "metro_lines" => MetroLine::class,
        "metro_station" => MetroStation::class,
        "metro_stations" => MetroStation::class,
        "geo_country" => Country::class,
        "geo_countries" => Country::class,
        "geo_region" => Region::class,
        "geo_regions" => Region::class,
        "geo_city" => City::class,
        "geo_cities" => City::class,
        "geo_district" => District::class,
        "geo_districts" => District::class,
    ],
    "exclude" => ["created_at", "updated_at", "remember_token", "settings"],
    "admin" => [
        "list_mode" => env("CHANGELOG_ADMIN_LIST_MODE", "latest"),
        "limit" => (int) env("CHANGELOG_ADMIN_LIMIT", 20),
    ],
];
