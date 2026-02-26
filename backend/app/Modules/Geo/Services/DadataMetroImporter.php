<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Modules\Geo\Models\City;
use Modules\Geo\Models\MetroLine;
use Modules\Geo\Models\MetroStation;

final class DadataMetroImporter
{
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
     * @return array{city_id:string,metro_lines_created:int,metro_stations_created:int,total_requests:int}
     */
    public function importForCity(
        City $city,
        int $maxPrefixDepth = 1,
        int $maxRequests = 500,
        int $sleepMs = 0,
    ): array {
        $this->requestsCount = 0;
        $items = $this->collectMetroItems(
            (string) $city->name,
            $maxPrefixDepth,
            $maxRequests,
            $sleepMs,
        );

        $metroLinesCreated = 0;
        $metroStationsCreated = 0;

        foreach ($items as $item) {
            $lineLookup = ["city_id" => (string) $city->id];
            if ($item["line_id"] !== null && $item["line_id"] !== "") {
                $lineLookup["line_id"] = $item["line_id"];
            } else {
                $lineLookup["external_id"] = $item["line_external_id"];
            }

            $line = MetroLine::query()->firstOrCreate($lineLookup, [
                "name" => $item["line_name"],
                "external_id" => $item["line_external_id"],
                "line_id" => $item["line_id"],
                "color" => $item["line_color"],
                "source" => "dadata",
            ]);
            if ($line->wasRecentlyCreated) {
                $metroLinesCreated++;
            }

            $station = MetroStation::query()->firstOrCreate(
                [
                    "city_id" => (string) $city->id,
                    "external_id" => $item["station_external_id"],
                ],
                [
                    "metro_line_id" => (string) $line->id,
                    "name" => $item["station_name"],
                    "line_id" => $item["line_id"],
                    "geo_lat" => $item["geo_lat"],
                    "geo_lon" => $item["geo_lon"],
                    "is_closed" => $item["is_closed"],
                    "source" => "dadata",
                ],
            );
            if ($station->wasRecentlyCreated) {
                $metroStationsCreated++;
            }
        }

        return [
            "city_id" => (string) $city->id,
            "metro_lines_created" => $metroLinesCreated,
            "metro_stations_created" => $metroStationsCreated,
            "total_requests" => $this->requestsCount,
        ];
    }

    /**
     * @return list<array{station_name:string,station_external_id:string,line_id:?string,line_name:string,line_external_id:string,line_color:?string,geo_lat:?float,geo_lon:?float,is_closed:?bool}>
     */
    private function collectMetroItems(
        string $cityName,
        int $maxPrefixDepth,
        int $maxRequests,
        int $sleepMs,
    ): array {
        $itemsByKey = [];
        $crawl = function (string $prefix, int $depth) use (
            &$crawl,
            &$itemsByKey,
            $cityName,
            $maxPrefixDepth,
            $maxRequests,
            $sleepMs,
        ): void {
            if ($this->requestsCount >= $maxRequests) {
                return;
            }

            $this->requestsCount++;
            $query = trim($cityName . " " . $prefix);
            $suggestions = $this->client->suggestMetro($query, ["count" => 20]);

            foreach ($suggestions as $s) {
                $data = $s["data"] ?? null;
                if (!is_array($data)) {
                    continue;
                }

                $stationName = trim((string) ($data["name"] ?? ""));
                if ($stationName === "") {
                    continue;
                }

                $lineId = trim((string) ($data["line_id"] ?? ""));
                $lineId = $lineId !== "" ? $lineId : null;
                $lineName = trim((string) ($data["line_name"] ?? ""));
                if ($lineName === "") {
                    $lineName = "Без линии";
                }

                $cityFiasId = trim((string) ($data["city_fias_id"] ?? ""));
                $lineExternalBase = $lineId ?? mb_strtolower($lineName);
                $lineExternalId =
                    $cityFiasId !== "" ? $cityFiasId . ":" . $lineExternalBase : $lineExternalBase;
                $stationExternalId = sha1($lineExternalId . ":" . mb_strtolower($stationName));

                $color = trim((string) ($data["color"] ?? ""));
                if ($color !== "" && $color[0] !== "#") {
                    $color = "#" . $color;
                }

                $key = mb_strtolower($stationName) . "|" . mb_strtolower($lineExternalId);
                $itemsByKey[$key] = [
                    "station_name" => $stationName,
                    "station_external_id" => $stationExternalId,
                    "line_id" => $lineId,
                    "line_name" => $lineName,
                    "line_external_id" => $lineExternalId,
                    "line_color" => $color !== "" ? strtoupper($color) : null,
                    "geo_lat" => is_numeric($data["geo_lat"] ?? null)
                        ? (float) $data["geo_lat"]
                        : null,
                    "geo_lon" => is_numeric($data["geo_lon"] ?? null)
                        ? (float) $data["geo_lon"]
                        : null,
                    "is_closed" => is_bool($data["is_closed"] ?? null) ? $data["is_closed"] : null,
                ];
            }

            $isRoot = $depth === 0 && $prefix === "";
            if ($depth >= $maxPrefixDepth || (count($suggestions) < 20 && !$isRoot)) {
                return;
            }

            foreach (self::PREFIX_SYMBOLS as $symbol) {
                $crawl($prefix . $symbol, $depth + 1);
                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        };

        $crawl("", 0);

        return array_values($itemsByKey);
    }
}
