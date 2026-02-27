<?php

declare(strict_types=1);

namespace Modules\Metro\Console\Commands;

use Illuminate\Console\Command;
use Modules\Geo\Models\City;
use Modules\Metro\Services\DadataMetroImporter;

final class ImportMetroFromDadataCommand extends Command
{
    protected $signature = "geo:import-metro-dadata
        {--city-id= : Existing city UUID}
        {--city= : Existing city name}
        {--max-prefix-depth=1 : Prefix depth for suggest/metro crawl}
        {--max-requests=500 : Safety limit for DaData requests}
        {--sleep-ms=0 : Delay between requests in milliseconds}";

    protected $description = "Import metro lines/stations from DaData suggest/metro";

    public function handle(DadataMetroImporter $importer): int
    {
        $cityId = trim((string) $this->option("city-id"));
        $cityName = trim((string) $this->option("city"));

        $city = null;
        if ($cityId !== "") {
            $city = City::query()->find($cityId);
        } elseif ($cityName !== "") {
            $city = City::query()->where("name", $cityName)->orderBy("created_at")->first();
        }

        if (!$city) {
            $this->error("City not found. Use --city-id or --city with existing city in DB.");

            return self::FAILURE;
        }

        $stats = $importer->importForCity(
            $city,
            maxPrefixDepth: max(0, (int) $this->option("max-prefix-depth")),
            maxRequests: max(1, (int) $this->option("max-requests")),
            sleepMs: max(0, (int) $this->option("sleep-ms")),
        );

        $this->table(
            ["Metric", "Value"],
            [
                ["City ID", $stats["city_id"]],
                ["Metro lines created", (string) $stats["metro_lines_created"]],
                ["Metro stations created", (string) $stats["metro_stations_created"]],
                ["DaData requests", (string) $stats["total_requests"]],
            ],
        );

        return self::SUCCESS;
    }
}
