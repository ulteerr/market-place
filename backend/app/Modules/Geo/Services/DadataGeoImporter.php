<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Support\Arr;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\MetroLine;
use Modules\Geo\Models\MetroStation;
use Modules\Geo\Models\Region;

final class DadataGeoImporter
{
    private const RUSSIA_ISO = "RUS";

    private const RUSSIA_NAME = "Россия";

    private const METRO_UNKNOWN_LINE = "Без линии";

    /** @var list<string> */
    private const PREFIX_SYMBOLS = [
        "а",
        "б",
        "в",
        "г",
        "д",
        "е",
        "ё",
        "ж",
        "з",
        "и",
        "й",
        "к",
        "л",
        "м",
        "н",
        "о",
        "п",
        "р",
        "с",
        "т",
        "у",
        "ф",
        "х",
        "ц",
        "ч",
        "ш",
        "щ",
        "э",
        "ю",
        "я",
        "0",
        "1",
        "2",
        "3",
        "4",
        "5",
        "6",
        "7",
        "8",
        "9",
    ];

    private int $requestsCount = 0;

    public function __construct(private readonly DadataAddressClient $client) {}

    /**
     * @return array{country_id:string,regions_created:int,cities_created:int,districts_created:int,metro_lines_created:int,metro_stations_created:int,total_requests:int}
     */
    public function importRussia(
        int $maxPrefixDepth = 2,
        int $maxRequests = 5000,
        int $sleepMs = 100,
        ?string $region = null,
        bool $withMetro = false,
        int $metroPrefixDepth = 1,
    ): array {
        $this->requestsCount = 0;

        $country = Country::query()->firstOrCreate(
            ["iso_code" => self::RUSSIA_ISO],
            ["name" => self::RUSSIA_NAME],
        );

        if ($country->name !== self::RUSSIA_NAME) {
            $country->update(["name" => self::RUSSIA_NAME]);
        }

        $normalizedRegion = $region !== null ? trim($region) : null;

        if ($normalizedRegion !== null && $normalizedRegion !== "") {
            $regionRows = $this->collectSingleRegion(
                region: $normalizedRegion,
                maxRequests: $maxRequests,
            );
        } else {
            $regionRows = $this->collectBoundedNames(
                bound: "region",
                locations: [
                    [
                        "country_iso_code" => "RU",
                    ],
                ],
                maxPrefixDepth: $maxPrefixDepth,
                maxRequests: $maxRequests,
                sleepMs: $sleepMs,
                nameResolver: static function (array $data): ?string {
                    $regionName = trim((string) Arr::get($data, "region", ""));

                    return $regionName !== "" ? $regionName : null;
                },
                keyResolver: static function (array $data, string $name): string {
                    $fiasId = trim((string) Arr::get($data, "region_fias_id", ""));

                    return $fiasId !== "" ? "fias:" . $fiasId : "name:" . mb_strtolower($name);
                },
            );
        }

        $regionsCreated = 0;
        $citiesCreated = 0;
        $districtsCreated = 0;
        $metroLinesCreated = 0;
        $metroStationsCreated = 0;

        foreach ($regionRows as $regionRow) {
            $regionModel = Region::query()->firstOrCreate([
                "country_id" => (string) $country->id,
                "name" => $regionRow["name"],
            ]);

            if ($regionModel->wasRecentlyCreated) {
                $regionsCreated++;
            }

            $regionLocations = [];
            $regionFiasId = trim((string) Arr::get($regionRow["data"], "region_fias_id", ""));
            if ($regionFiasId !== "") {
                $regionLocations[] = [
                    "country_iso_code" => "RU",
                    "region_fias_id" => $regionFiasId,
                ];
            } else {
                $regionLocations[] = [
                    "country_iso_code" => "RU",
                    "region" => $regionRow["name"],
                ];
            }

            $cityRows = array_merge(
                $this->collectBoundedNames(
                    bound: "city",
                    locations: $regionLocations,
                    maxPrefixDepth: $maxPrefixDepth,
                    maxRequests: $maxRequests,
                    sleepMs: $sleepMs,
                    nameResolver: static function (array $data): ?string {
                        $city = trim((string) Arr::get($data, "city", ""));

                        return $city !== "" ? $city : null;
                    },
                    keyResolver: static function (array $data, string $name): string {
                        $fiasId = trim((string) Arr::get($data, "city_fias_id", ""));

                        return $fiasId !== "" ? "fias:" . $fiasId : "name:" . mb_strtolower($name);
                    },
                ),
                $this->collectBoundedNames(
                    bound: "settlement",
                    locations: $regionLocations,
                    maxPrefixDepth: $maxPrefixDepth,
                    maxRequests: $maxRequests,
                    sleepMs: $sleepMs,
                    nameResolver: static function (array $data): ?string {
                        $settlement = trim((string) Arr::get($data, "settlement", ""));

                        return $settlement !== "" ? $settlement : null;
                    },
                    keyResolver: static function (array $data, string $name): string {
                        $fiasId = trim((string) Arr::get($data, "settlement_fias_id", ""));

                        return $fiasId !== "" ? "fias:" . $fiasId : "name:" . mb_strtolower($name);
                    },
                ),
            );

            $cityRowsByName = [];
            foreach ($cityRows as $row) {
                $cityName = $row["name"];
                $cityRowsByName[mb_strtolower($cityName)] = $row;
            }

            foreach ($cityRowsByName as $cityRow) {
                $city = City::query()->firstOrCreate(
                    [
                        "region_id" => (string) $regionModel->id,
                        "name" => $cityRow["name"],
                    ],
                    [
                        "country_id" => (string) $country->id,
                    ],
                );

                if ($city->wasRecentlyCreated) {
                    $citiesCreated++;
                }

                $cityLocations = $this->buildCityLocations(
                    $cityRow["data"],
                    $regionRow["name"],
                    $cityRow["name"],
                );

                $districtNames = $this->extractDistrictNamesFromData($cityRow["data"]);
                if ($districtNames === []) {
                    $districtRows = $this->collectBoundedNames(
                        bound: "city_district",
                        locations: $cityLocations,
                        maxPrefixDepth: min($maxPrefixDepth, 1),
                        maxRequests: $maxRequests,
                        sleepMs: $sleepMs,
                        nameResolver: static function (array $data): ?string {
                            $district = trim((string) Arr::get($data, "city_district", ""));

                            return $district !== "" ? $district : null;
                        },
                        keyResolver: static function (array $data, string $name): string {
                            $fiasId = trim((string) Arr::get($data, "city_district_fias_id", ""));

                            return $fiasId !== ""
                                ? "fias:" . $fiasId
                                : "name:" . mb_strtolower($name);
                        },
                    );

                    foreach ($districtRows as $districtRow) {
                        $districtNames[] = $districtRow["name"];
                    }
                }

                $districtNames = $this->uniqueNames($districtNames);
                foreach ($districtNames as $districtName) {
                    $district = District::query()->firstOrCreate([
                        "city_id" => (string) $city->id,
                        "name" => $districtName,
                    ]);

                    if ($district->wasRecentlyCreated) {
                        $districtsCreated++;
                    }
                }

                if (!$withMetro) {
                    continue;
                }

                $metroItems = $this->collectMetroItemsForCity(
                    cityName: $cityRow["name"],
                    maxPrefixDepth: $metroPrefixDepth,
                    maxRequests: $maxRequests,
                    sleepMs: $sleepMs,
                );

                foreach ($metroItems as $metroItem) {
                    $lineLookup = [
                        "city_id" => (string) $city->id,
                    ];
                    if ($metroItem["line_id"] !== null && $metroItem["line_id"] !== "") {
                        $lineLookup["line_id"] = $metroItem["line_id"];
                    } else {
                        $lineLookup["external_id"] = $metroItem["line_external_id"];
                    }

                    $line = MetroLine::query()->firstOrCreate($lineLookup, [
                        "name" => $metroItem["line_name"],
                        "external_id" => $metroItem["line_external_id"],
                        "line_id" => $metroItem["line_id"],
                        "color" => $metroItem["line_color"],
                        "source" => "dadata",
                    ]);

                    if ($line->wasRecentlyCreated) {
                        $metroLinesCreated++;
                    } else {
                        $lineUpdates = [];
                        if ($line->color === null && $metroItem["line_color"] !== null) {
                            $lineUpdates["color"] = $metroItem["line_color"];
                        }
                        if (
                            $line->external_id === null &&
                            $metroItem["line_external_id"] !== null
                        ) {
                            $lineUpdates["external_id"] = $metroItem["line_external_id"];
                        }
                        if (
                            $line->name !== $metroItem["line_name"] &&
                            $metroItem["line_name"] !== ""
                        ) {
                            $lineUpdates["name"] = $metroItem["line_name"];
                        }
                        if ($lineUpdates !== []) {
                            $line->update($lineUpdates);
                        }
                    }

                    $station = MetroStation::query()->firstOrCreate(
                        [
                            "city_id" => (string) $city->id,
                            "external_id" => $metroItem["station_external_id"],
                        ],
                        [
                            "metro_line_id" => (string) $line->id,
                            "name" => $metroItem["station_name"],
                            "line_id" => $metroItem["line_id"],
                            "geo_lat" => $metroItem["geo_lat"],
                            "geo_lon" => $metroItem["geo_lon"],
                            "is_closed" => $metroItem["is_closed"],
                            "source" => "dadata",
                        ],
                    );

                    if ($station->wasRecentlyCreated) {
                        $metroStationsCreated++;
                    } else {
                        $stationUpdates = [];
                        if ((string) $station->metro_line_id !== (string) $line->id) {
                            $stationUpdates["metro_line_id"] = (string) $line->id;
                        }
                        if ($station->line_id === null && $metroItem["line_id"] !== null) {
                            $stationUpdates["line_id"] = $metroItem["line_id"];
                        }
                        if ($station->geo_lat === null && $metroItem["geo_lat"] !== null) {
                            $stationUpdates["geo_lat"] = $metroItem["geo_lat"];
                        }
                        if ($station->geo_lon === null && $metroItem["geo_lon"] !== null) {
                            $stationUpdates["geo_lon"] = $metroItem["geo_lon"];
                        }
                        if ($station->is_closed === null && $metroItem["is_closed"] !== null) {
                            $stationUpdates["is_closed"] = $metroItem["is_closed"];
                        }
                        if ($stationUpdates !== []) {
                            $station->update($stationUpdates);
                        }
                    }
                }
            }
        }

        return [
            "country_id" => (string) $country->id,
            "regions_created" => $regionsCreated,
            "cities_created" => $citiesCreated,
            "districts_created" => $districtsCreated,
            "metro_lines_created" => $metroLinesCreated,
            "metro_stations_created" => $metroStationsCreated,
            "total_requests" => $this->requestsCount,
        ];
    }

    /**
     * @return list<array{name:string,data:array}>
     */
    private function collectSingleRegion(string $region, int $maxRequests): array
    {
        $rowsByKey = [];

        $suggestions = $this->requestSuggestions(
            query: $region,
            bound: "region",
            locations: [["country_iso_code" => "RU"]],
            maxRequests: $maxRequests,
        );

        $regionNeedle = mb_strtolower($region);

        foreach ($suggestions as $suggestion) {
            $data = Arr::get($suggestion, "data", []);
            if (!is_array($data)) {
                continue;
            }

            $regionName = trim((string) Arr::get($data, "region", ""));
            if ($regionName === "") {
                continue;
            }

            $regionHaystack = mb_strtolower($regionName);
            if (
                !str_contains($regionHaystack, $regionNeedle) &&
                !str_contains($regionNeedle, $regionHaystack)
            ) {
                continue;
            }

            $fiasId = trim((string) Arr::get($data, "region_fias_id", ""));
            $key = $fiasId !== "" ? "fias:" . $fiasId : "name:" . mb_strtolower($regionName);

            $rowsByKey[$key] = [
                "name" => $regionName,
                "data" => $data,
            ];
        }

        return array_values($rowsByKey);
    }

    /**
     * @param array<int, array<string, string>> $locations
     * @param callable(array): ?string          $nameResolver
     * @param callable(array, string): string   $keyResolver
     *
     * @return list<array{name:string,data:array}>
     */
    private function collectBoundedNames(
        string $bound,
        array $locations,
        int $maxPrefixDepth,
        int $maxRequests,
        int $sleepMs,
        callable $nameResolver,
        callable $keyResolver,
    ): array {
        $itemsByKey = [];

        $this->crawlBounded(
            prefix: "",
            depth: 0,
            bound: $bound,
            locations: $locations,
            maxPrefixDepth: $maxPrefixDepth,
            maxRequests: $maxRequests,
            sleepMs: $sleepMs,
            onSuggestions: function (array $suggestions) use (
                &$itemsByKey,
                $nameResolver,
                $keyResolver,
            ): void {
                foreach ($suggestions as $suggestion) {
                    $data = Arr::get($suggestion, "data", []);
                    if (!is_array($data)) {
                        continue;
                    }

                    $name = $nameResolver($data);
                    if ($name === null) {
                        continue;
                    }

                    $key = $keyResolver($data, $name);
                    $itemsByKey[$key] = [
                        "name" => $name,
                        "data" => $data,
                    ];
                }
            },
        );

        return array_values($itemsByKey);
    }

    /**
     * @return list<array{
     *   station_name:string,
     *   station_external_id:string,
     *   line_id:string,
     *   line_name:string,
     *   line_external_id:string,
     *   line_color:?string,
     *   geo_lat:?float,
     *   geo_lon:?float,
     *   is_closed:?bool
     * }>
     */
    private function collectMetroItemsForCity(
        string $cityName,
        int $maxPrefixDepth,
        int $maxRequests,
        int $sleepMs,
    ): array {
        if ($maxPrefixDepth < 0) {
            return [];
        }

        $itemsByKey = [];

        $this->crawlMetro(
            cityName: $cityName,
            prefix: "",
            depth: 0,
            maxPrefixDepth: $maxPrefixDepth,
            maxRequests: $maxRequests,
            sleepMs: $sleepMs,
            onSuggestions: function (array $suggestions) use (&$itemsByKey): void {
                foreach ($suggestions as $suggestion) {
                    $data = Arr::get($suggestion, "data", []);
                    if (!is_array($data)) {
                        continue;
                    }

                    $stationName = trim((string) Arr::get($data, "name", ""));
                    if ($stationName === "") {
                        continue;
                    }

                    $lineId = trim((string) Arr::get($data, "line_id", ""));

                    $lineName = trim((string) Arr::get($data, "line_name", ""));
                    if ($lineName === "") {
                        $lineName = self::METRO_UNKNOWN_LINE;
                    }

                    $cityFiasId = trim((string) Arr::get($data, "city_fias_id", ""));
                    $lineExternalIdBase = $lineId !== "" ? $lineId : mb_strtolower($lineName);
                    $lineExternalId =
                        $cityFiasId !== ""
                            ? $cityFiasId . ":" . $lineExternalIdBase
                            : $lineExternalIdBase;
                    $stationExternalId = sha1($lineExternalId . ":" . mb_strtolower($stationName));

                    $lineColor = $this->normalizeMetroColor(
                        trim((string) Arr::get($data, "color", "")),
                    );
                    $geoLat = Arr::get($data, "geo_lat");
                    $geoLon = Arr::get($data, "geo_lon");
                    $isClosed = Arr::get($data, "is_closed");

                    $key = mb_strtolower($stationName) . "|" . mb_strtolower($lineExternalId);
                    $itemsByKey[$key] = [
                        "station_name" => $stationName,
                        "station_external_id" => $stationExternalId,
                        "line_id" => $lineId !== "" ? $lineId : null,
                        "line_name" => $lineName,
                        "line_external_id" => $lineExternalId,
                        "line_color" => $lineColor,
                        "geo_lat" => is_numeric($geoLat) ? (float) $geoLat : null,
                        "geo_lon" => is_numeric($geoLon) ? (float) $geoLon : null,
                        "is_closed" => is_bool($isClosed) ? $isClosed : null,
                    ];
                }
            },
        );

        return array_values($itemsByKey);
    }

    /**
     * @param callable(array): void $onSuggestions
     */
    private function crawlMetro(
        string $cityName,
        string $prefix,
        int $depth,
        int $maxPrefixDepth,
        int $maxRequests,
        int $sleepMs,
        callable $onSuggestions,
    ): void {
        $query = trim($cityName . " " . $prefix);
        $suggestions = $this->requestMetroSuggestions($query, $maxRequests);
        $onSuggestions($suggestions);

        $isRootLevel = $depth === 0 && $prefix === "";
        if ($depth >= $maxPrefixDepth || (count($suggestions) < 20 && !$isRootLevel)) {
            return;
        }

        foreach (self::PREFIX_SYMBOLS as $symbol) {
            $this->crawlMetro(
                cityName: $cityName,
                prefix: $prefix . $symbol,
                depth: $depth + 1,
                maxPrefixDepth: $maxPrefixDepth,
                maxRequests: $maxRequests,
                sleepMs: $sleepMs,
                onSuggestions: $onSuggestions,
            );

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
    }

    /**
     * @param array<int, array<string, string>> $locations
     * @param callable(array): void             $onSuggestions
     */
    private function crawlBounded(
        string $prefix,
        int $depth,
        string $bound,
        array $locations,
        int $maxPrefixDepth,
        int $maxRequests,
        int $sleepMs,
        callable $onSuggestions,
    ): void {
        if ($depth > 0 && $prefix === "") {
            return;
        }

        $suggestions = $this->requestSuggestions(
            query: $prefix,
            bound: $bound,
            locations: $locations,
            maxRequests: $maxRequests,
        );

        $onSuggestions($suggestions);

        $isRootLevel = $depth === 0 && $prefix === "";

        if ($depth >= $maxPrefixDepth || (count($suggestions) < 20 && !$isRootLevel)) {
            return;
        }

        foreach (self::PREFIX_SYMBOLS as $symbol) {
            $this->crawlBounded(
                prefix: $prefix . $symbol,
                depth: $depth + 1,
                bound: $bound,
                locations: $locations,
                maxPrefixDepth: $maxPrefixDepth,
                maxRequests: $maxRequests,
                sleepMs: $sleepMs,
                onSuggestions: $onSuggestions,
            );

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
    }

    /**
     * @param array<int, array<string, string>> $locations
     *
     * @return list<array<string, mixed>>
     */
    private function requestSuggestions(
        string $query,
        string $bound,
        array $locations,
        int $maxRequests,
    ): array {
        if ($this->requestsCount >= $maxRequests) {
            return [];
        }

        $this->requestsCount++;

        return $this->client->suggestAddress($query, [
            "from_bound" => ["value" => $bound],
            "to_bound" => ["value" => $bound],
            "locations" => $locations,
            "restrict_value" => true,
            "count" => 20,
        ]);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function requestMetroSuggestions(string $query, int $maxRequests): array
    {
        if ($this->requestsCount >= $maxRequests) {
            return [];
        }

        $this->requestsCount++;

        return $this->client->suggestMetro($query, [
            "count" => 20,
        ]);
    }

    /**
     * @param array<string, mixed> $cityData
     *
     * @return list<string>
     */
    private function extractDistrictNamesFromData(array $cityData): array
    {
        $result = [];

        foreach (["city_district", "city_area"] as $field) {
            $name = trim((string) Arr::get($cityData, $field, ""));
            if ($name !== "") {
                $result[] = $name;
            }
        }

        return $this->uniqueNames($result);
    }

    /**
     * @param array<string, mixed> $cityData
     * @return array<int, array<string, string>>
     */
    private function buildCityLocations(
        array $cityData,
        string $regionName,
        string $cityName,
    ): array {
        $cityFiasId = trim((string) Arr::get($cityData, "city_fias_id", ""));
        if ($cityFiasId === "") {
            $cityFiasId = trim((string) Arr::get($cityData, "settlement_fias_id", ""));
        }

        if ($cityFiasId !== "") {
            return [
                [
                    "country_iso_code" => "RU",
                    "city_fias_id" => $cityFiasId,
                ],
            ];
        }

        return [
            [
                "country_iso_code" => "RU",
                "region" => $regionName,
                "city" => $cityName,
            ],
        ];
    }

    private function normalizeMetroColor(string $color): ?string
    {
        if ($color === "") {
            return null;
        }

        $normalized = strtoupper($color);
        if ($normalized[0] !== "#") {
            $normalized = "#" . $normalized;
        }

        return $normalized;
    }

    /**
     * @param list<string> $names
     * @return list<string>
     */
    private function uniqueNames(array $names): array
    {
        $resultByKey = [];
        foreach ($names as $name) {
            $normalizedName = trim($name);
            if ($normalizedName === "") {
                continue;
            }

            $resultByKey[mb_strtolower($normalizedName)] = $normalizedName;
        }

        return array_values($resultByKey);
    }
}
