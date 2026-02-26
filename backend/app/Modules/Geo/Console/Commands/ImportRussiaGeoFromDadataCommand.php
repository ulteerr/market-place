<?php

declare(strict_types=1);

namespace Modules\Geo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Geo\Services\DadataGeoImporter;

final class ImportRussiaGeoFromDadataCommand extends Command
{
    protected $signature = "geo:import-russia-dadata
        {--max-prefix-depth=2 : Prefix crawl depth. Increase to collect more data}
        {--max-requests=5000 : Safety limit for API requests}
        {--sleep-ms=100 : Delay between prefix-expansion requests in milliseconds}
        {--region= : Import only one region (for example: Санкт-Петербург)}
        {--with-metro : Try to import metro lines and stations}
        {--metro-prefix-depth=1 : Prefix depth for metro station discovery}";

    protected $description = "Import Russian geo dictionary (country, regions, cities, districts) from DaData";

    public function handle(DadataGeoImporter $importer): int
    {
        $maxPrefixDepth = max(0, (int) $this->option("max-prefix-depth"));
        $maxRequests = max(1, (int) $this->option("max-requests"));
        $sleepMs = max(0, (int) $this->option("sleep-ms"));
        $region = trim((string) $this->option("region"));
        $withMetro = (bool) $this->option("with-metro");
        $metroPrefixDepth = max(0, (int) $this->option("metro-prefix-depth"));

        $this->info("Starting DaData import for Russia...");
        $this->line(
            "Options: depth={$maxPrefixDepth}, requests={$maxRequests}, sleep_ms={$sleepMs}, region=" .
                ($region !== "" ? $region : "ALL") .
                ", with_metro=" .
                ($withMetro ? "yes" : "no") .
                ", metro_depth={$metroPrefixDepth}",
        );

        $stats = $importer->importRussia(
            maxPrefixDepth: $maxPrefixDepth,
            maxRequests: $maxRequests,
            sleepMs: $sleepMs,
            region: $region !== "" ? $region : null,
            withMetro: $withMetro,
            metroPrefixDepth: $metroPrefixDepth,
        );

        $this->info("Import completed.");
        $this->table(
            ["Metric", "Value"],
            [
                ["Country ID", $stats["country_id"]],
                ["Regions created", (string) $stats["regions_created"]],
                ["Cities created", (string) $stats["cities_created"]],
                ["Districts created", (string) $stats["districts_created"]],
                ["Metro lines created", (string) $stats["metro_lines_created"]],
                ["Metro stations created", (string) $stats["metro_stations_created"]],
                ["DaData requests", (string) $stats["total_requests"]],
            ],
        );

        return self::SUCCESS;
    }
}
