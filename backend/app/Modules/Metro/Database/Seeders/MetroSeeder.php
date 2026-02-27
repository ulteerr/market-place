<?php

declare(strict_types=1);

namespace Modules\Metro\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\Region;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Models\MetroStation;

final class MetroSeeder extends Seeder
{
    public function run(): void
    {
        $moscow = $this->firstOrCreateCity(
            countryIso: "RUS",
            countryName: "Россия",
            regionName: "Москва",
            cityName: "Москва",
        );

        $spb = $this->firstOrCreateCity(
            countryIso: "RUS",
            countryName: "Россия",
            regionName: "Санкт-Петербург",
            cityName: "Санкт-Петербург",
        );

        $datasets = [
            [
                "city_id" => (string) $moscow->id,
                "lines" => [
                    [
                        "name" => "Сокольническая",
                        "line_id" => "1",
                        "color" => "#D6083B",
                        "stations" => ["Охотный ряд", "Лубянка", "Чистые пруды"],
                    ],
                    [
                        "name" => "Арбатско-Покровская",
                        "line_id" => "3",
                        "color" => "#0078C9",
                        "stations" => ["Арбатская", "Курская", "Бауманская"],
                    ],
                ],
            ],
            [
                "city_id" => (string) $spb->id,
                "lines" => [
                    [
                        "name" => "Невско-Василеостровская",
                        "line_id" => "3",
                        "color" => "#009A49",
                        "stations" => ["Гостиный двор", "Василеостровская", "Приморская"],
                    ],
                ],
            ],
        ];

        foreach ($datasets as $cityData) {
            foreach ($cityData["lines"] as $lineData) {
                $line = MetroLine::query()->firstOrCreate(
                    [
                        "city_id" => $cityData["city_id"],
                        "name" => $lineData["name"],
                    ],
                    [
                        "line_id" => $lineData["line_id"],
                        "external_id" => strtolower($lineData["name"]),
                        "color" => $lineData["color"],
                        "source" => "manual",
                    ],
                );

                foreach ($lineData["stations"] as $stationName) {
                    MetroStation::query()->firstOrCreate(
                        [
                            "city_id" => $cityData["city_id"],
                            "metro_line_id" => (string) $line->id,
                            "name" => $stationName,
                        ],
                        [
                            "line_id" => $lineData["line_id"],
                            "external_id" => sha1(
                                $cityData["city_id"] . "|" . $lineData["name"] . "|" . $stationName,
                            ),
                            "source" => "manual",
                        ],
                    );
                }
            }
        }
    }

    private function firstOrCreateCity(
        string $countryIso,
        string $countryName,
        string $regionName,
        string $cityName,
    ): City {
        $country = Country::query()->firstOrCreate(
            ["iso_code" => $countryIso],
            ["name" => $countryName],
        );

        $region = Region::query()->firstOrCreate([
            "country_id" => (string) $country->id,
            "name" => $regionName,
        ]);

        return City::query()->firstOrCreate(
            [
                "region_id" => (string) $region->id,
                "name" => $cityName,
            ],
            [
                "country_id" => (string) $country->id,
            ],
        );
    }
}
