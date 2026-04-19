<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Http\Request;
use Modules\Geo\Models\City;

final class PublicCityDetectionService
{
    public function __construct(
        private readonly CitiesService $citiesService,
        private readonly IpLocationLookupService $ipLocationLookupService,
    ) {}

    /**
     * @return array{city: City, resolved_by: 'ip'|'fallback'}|null
     */
    public function detect(Request $request): ?array
    {
        $clientIp = $this->resolveClientIp($request);

        if ($clientIp !== "") {
            $location = $this->ipLocationLookupService->lookup($clientIp);

            if ($location) {
                $matchedCity = $this->citiesService->findByNames(
                    $location["city_name"],
                    $location["country_name"],
                    $location["region_name"],
                );

                if ($matchedCity) {
                    return [
                        "city" => $matchedCity,
                        "resolved_by" => "ip",
                    ];
                }
            }
        }

        $fallbackCity = $this->citiesService->firstById();
        if (!$fallbackCity) {
            return null;
        }

        return [
            "city" => $fallbackCity,
            "resolved_by" => "fallback",
        ];
    }

    private function resolveClientIp(Request $request): string
    {
        $candidateHeaders = ["CF-Connecting-IP", "True-Client-IP", "X-Forwarded-For", "X-Real-IP"];

        foreach ($candidateHeaders as $header) {
            $rawValue = trim((string) $request->header($header, ""));
            if ($rawValue === "") {
                continue;
            }

            $value =
                $header === "X-Forwarded-For" ? trim(explode(",", $rawValue)[0] ?? "") : $rawValue;

            if ($value !== "") {
                return $value;
            }
        }

        return trim((string) $request->ip());
    }
}
