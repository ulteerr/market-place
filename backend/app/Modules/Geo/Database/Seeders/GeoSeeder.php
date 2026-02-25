<?php

declare(strict_types=1);

namespace Modules\Geo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;

final class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                "name" => "Россия",
                "iso_code" => "RUS",
                "regions" => [
                    [
                        "name" => "Москва",
                        "cities" => [
                            [
                                "name" => "Москва",
                                "districts" => ["Центральный", "Северный", "Южный"],
                            ],
                        ],
                    ],
                    [
                        "name" => "Санкт-Петербург",
                        "cities" => [
                            [
                                "name" => "Санкт-Петербург",
                                "districts" => ["Адмиралтейский", "Выборгский", "Приморский"],
                            ],
                        ],
                    ],
                    [
                        "name" => "Республика Татарстан",
                        "cities" => [
                            [
                                "name" => "Казань",
                                "districts" => ["Вахитовский", "Ново-Савиновский", "Приволжский"],
                            ],
                        ],
                    ],
                ],
            ],
            [
                "name" => "Казахстан",
                "iso_code" => "KAZ",
                "regions" => [
                    [
                        "name" => "Алматинская область",
                        "cities" => [
                            [
                                "name" => "Алматы",
                                "districts" => ["Алмалинский", "Ауэзовский", "Бостандыкский"],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($countries as $countryData) {
            $country = Country::query()->firstOrCreate(
                ["iso_code" => $countryData["iso_code"]],
                ["name" => $countryData["name"]],
            );

            foreach ($countryData["regions"] as $regionData) {
                $region = Region::query()->firstOrCreate([
                    "country_id" => (string) $country->id,
                    "name" => $regionData["name"],
                ]);

                foreach ($regionData["cities"] as $cityData) {
                    $city = City::query()->firstOrCreate(
                        [
                            "region_id" => (string) $region->id,
                            "name" => $cityData["name"],
                        ],
                        [
                            "country_id" => (string) $country->id,
                        ],
                    );

                    foreach ($cityData["districts"] as $districtName) {
                        District::query()->firstOrCreate([
                            "city_id" => (string) $city->id,
                            "name" => $districtName,
                        ]);
                    }
                }
            }
        }
    }
}
