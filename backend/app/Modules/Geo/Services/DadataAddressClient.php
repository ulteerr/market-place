<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DadataAddressClient
{
    private const SUGGEST_ADDRESS_ENDPOINT = "/suggestions/api/4_1/rs/suggest/address";
    private const SUGGEST_METRO_ENDPOINT = "/suggestions/api/4_1/rs/suggest/metro";

    public function suggestAddress(string $query, array $payload = []): array
    {
        return $this->suggest(self::SUGGEST_ADDRESS_ENDPOINT, $query, $payload);
    }

    public function suggestMetro(string $query, array $payload = []): array
    {
        return $this->suggest(self::SUGGEST_METRO_ENDPOINT, $query, $payload);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function suggest(string $endpoint, string $query, array $payload = []): array
    {
        $token = trim((string) config("services.dadata.token", ""));
        if ($token === "") {
            throw new RuntimeException("DADATA_API_TOKEN is not configured");
        }

        $baseUrl = rtrim(
            (string) config("services.dadata.base_url", "https://suggestions.dadata.ru"),
            "/",
        );
        $secret = trim((string) config("services.dadata.secret", ""));
        $timeoutSeconds = max(5, (int) config("services.dadata.timeout_seconds", 30));
        $retryTimes = max(0, (int) config("services.dadata.retry_times", 2));
        $retryDelayMs = max(0, (int) config("services.dadata.retry_delay_ms", 500));

        $requestBody = array_merge(
            [
                "query" => $query,
                "count" => 20,
            ],
            $payload,
        );

        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeoutSeconds)
            ->retry($retryTimes, $retryDelayMs)
            ->withHeaders([
                "Authorization" => "Token " . $token,
            ]);

        if ($secret !== "") {
            $request = $request->withHeaders([
                "X-Secret" => $secret,
            ]);
        }

        $response = $request->post($endpoint, $requestBody);

        if (!$response->successful()) {
            throw new RuntimeException(
                "DaData API request failed with status " . $response->status(),
            );
        }

        $suggestions = Arr::get($response->json(), "suggestions", []);
        if (!is_array($suggestions)) {
            return [];
        }

        return $suggestions;
    }
}
