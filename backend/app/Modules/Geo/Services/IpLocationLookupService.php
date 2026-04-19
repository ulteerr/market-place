<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

final class IpLocationLookupService
{
    /**
     * @return array{city_name: string, country_name: string|null, region_name: string|null}|null
     */
    public function lookup(string $ip): ?array
    {
        $normalizedIp = trim($ip);
        if ($normalizedIp === "") {
            return null;
        }

        $baseUrl = rtrim((string) config("services.ip_geolocation.base_url", ""), "/");
        if ($baseUrl === "") {
            return null;
        }

        $timeoutSeconds = max(2, (int) config("services.ip_geolocation.timeout_seconds", 3));
        $retryTimes = max(0, (int) config("services.ip_geolocation.retry_times", 1));
        $retryDelayMs = max(0, (int) config("services.ip_geolocation.retry_delay_ms", 250));
        $language = trim((string) config("services.ip_geolocation.lang", "ru"));

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout($timeoutSeconds)
                ->retry($retryTimes, $retryDelayMs)
                ->get("/" . ltrim($normalizedIp, "/"), [
                    "lang" => $language !== "" ? $language : "ru",
                    "fields" => "success,message,city,country,region",
                ]);
        } catch (Throwable) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return null;
        }

        $success = Arr::get($payload, "success");
        if ($success === false) {
            return null;
        }

        $cityName = trim((string) Arr::get($payload, "city", ""));
        if ($cityName === "") {
            return null;
        }

        $countryName = trim((string) Arr::get($payload, "country", ""));
        $regionName = trim((string) Arr::get($payload, "region", ""));

        return [
            "city_name" => $cityName,
            "country_name" => $countryName !== "" ? $countryName : null,
            "region_name" => $regionName !== "" ? $regionName : null,
        ];
    }
}
