<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ObservabilityService
{
    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public function recordEvent(
        string $domain,
        string $component,
        string $event,
        string $status = "ok",
        string $severity = "info",
        ?int $durationMs = null,
        array $meta = [],
    ): array {
        if (!(bool) config("observability.enabled", true)) {
            return [];
        }

        $payload = [
            "timestamp" => Carbon::now()->toIso8601String(),
            "domain" => $domain,
            "component" => $component,
            "event" => $event,
            "severity" => $severity,
            "status" => $status,
            "duration_ms" => $durationMs,
            "request_id" => request()?->headers?->get("X-Request-Id"),
            "meta" => $this->maskSensitive($meta),
        ];

        $this->writeLog($payload);
        $this->updateSummary($payload);
        $this->appendIncident($payload);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $domain = null, int $incidentLimit = 50): array
    {
        $summary = $this->cache()->get(
            (string) config("observability.summary_key", "observability:summary"),
            [
                "domains" => [],
                "updated_at" => null,
            ],
        );

        if (!is_array($summary)) {
            $summary = [
                "domains" => [],
                "updated_at" => null,
            ];
        }

        $incidents = $this->cache()->get(
            (string) config("observability.incidents_key", "observability:incidents"),
            [],
        );
        if (!is_array($incidents)) {
            $incidents = [];
        }

        if ($domain !== null && $domain !== "") {
            $domains = is_array($summary["domains"] ?? null) ? $summary["domains"] : [];
            $summary["domains"] = isset($domains[$domain]) ? [$domain => $domains[$domain]] : [];

            $incidents = array_values(
                array_filter($incidents, fn($item): bool => ($item["domain"] ?? null) === $domain),
            );
        }

        return [
            "summary" => $summary,
            "incidents" => array_slice($incidents, 0, max(1, $incidentLimit)),
            "analytics" => $this->buildAnalytics($summary),
            "alerts" => $this->buildAlerts($summary),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeLog(array $payload): void
    {
        $logLevel = match ($payload["severity"] ?? "info") {
            "error" => "error",
            "warning" => "warning",
            default => "info",
        };

        try {
            Log::channel((string) config("observability.log_channel", "stack"))->log(
                $logLevel,
                "observability.event",
                $payload,
            );
        } catch (Throwable) {
            // Observability logging should never break business flow or tests
            // that mock logger methods narrowly.
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateSummary(array $payload): void
    {
        $key = (string) config("observability.summary_key", "observability:summary");
        $summary = $this->cache()->get($key, [
            "domains" => [],
            "updated_at" => null,
        ]);

        if (!is_array($summary)) {
            $summary = [
                "domains" => [],
                "updated_at" => null,
            ];
        }

        $domains = is_array($summary["domains"] ?? null) ? $summary["domains"] : [];
        $domain = (string) $payload["domain"];
        $event = (string) $payload["event"];
        $status = (string) $payload["status"];
        $durationMs = isset($payload["duration_ms"]) ? (int) $payload["duration_ms"] : null;

        if (!isset($domains[$domain]) || !is_array($domains[$domain])) {
            $domains[$domain] = [
                "events_total" => 0,
                "errors_total" => 0,
                "duration_total_ms" => 0,
                "duration_count" => 0,
                "events" => [],
                "last_event_at" => null,
            ];
        }

        $domainSummary = $domains[$domain];
        $domainSummary["events_total"] = (int) ($domainSummary["events_total"] ?? 0) + 1;
        if ($status !== "ok") {
            $domainSummary["errors_total"] = (int) ($domainSummary["errors_total"] ?? 0) + 1;
        }

        if ($durationMs !== null) {
            $domainSummary["duration_total_ms"] =
                (int) ($domainSummary["duration_total_ms"] ?? 0) + $durationMs;
            $domainSummary["duration_count"] = (int) ($domainSummary["duration_count"] ?? 0) + 1;
        }

        $events = is_array($domainSummary["events"] ?? null) ? $domainSummary["events"] : [];
        if (!isset($events[$event]) || !is_array($events[$event])) {
            $events[$event] = [];
        }
        $events[$event][$status] = (int) ($events[$event][$status] ?? 0) + 1;
        $domainSummary["events"] = $events;
        $domainSummary["last_event_at"] = $payload["timestamp"] ?? Carbon::now()->toIso8601String();
        $domains[$domain] = $domainSummary;

        $summary["domains"] = $domains;
        $summary["updated_at"] = Carbon::now()->toIso8601String();

        $this->cache()->forever($key, $summary);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function appendIncident(array $payload): void
    {
        $status = (string) ($payload["status"] ?? "ok");
        $severity = (string) ($payload["severity"] ?? "info");
        if ($status === "ok" && !in_array($severity, ["warning", "error"], true)) {
            return;
        }

        $key = (string) config("observability.incidents_key", "observability:incidents");
        $incidents = $this->cache()->get($key, []);
        if (!is_array($incidents)) {
            $incidents = [];
        }

        array_unshift($incidents, $payload);
        $incidents = array_slice(
            $incidents,
            0,
            max(1, (int) config("observability.incidents_limit", 200)),
        );

        $this->cache()->forever($key, $incidents);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function maskSensitive(array $meta): array
    {
        $sensitiveKeys = array_map(
            static fn(string $key): string => strtolower($key),
            (array) config("observability.sensitive_keys", []),
        );

        $maskedValue = (string) config("observability.masked_value", "[masked]");

        return $this->maskRecursively($meta, $sensitiveKeys, $maskedValue);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $sensitiveKeys
     * @return array<string, mixed>
     */
    private function maskRecursively(
        array $payload,
        array $sensitiveKeys,
        string $maskedValue,
    ): array {
        $masked = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (in_array($normalizedKey, $sensitiveKeys, true)) {
                $masked[$key] = $maskedValue;
                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->maskRecursively($value, $sensitiveKeys, $maskedValue);
                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }

    private function cache(): CacheRepository
    {
        $store = config("observability.cache_store");
        if (is_string($store) && $store !== "") {
            return Cache::store($store);
        }

        return Cache::store();
    }

    /**
     * @param array<string, mixed> $summary
     * @return array<string, array<string, float|int|null>>
     */
    private function buildAnalytics(array $summary): array
    {
        $domains = is_array($summary["domains"] ?? null) ? $summary["domains"] : [];
        $analytics = [];

        foreach ($domains as $domain => $domainSummary) {
            if (!is_array($domainSummary)) {
                continue;
            }

            $eventsTotal = (int) ($domainSummary["events_total"] ?? 0);
            $errorsTotal = (int) ($domainSummary["errors_total"] ?? 0);
            $durationTotalMs = (int) ($domainSummary["duration_total_ms"] ?? 0);
            $durationCount = (int) ($domainSummary["duration_count"] ?? 0);

            $errorRate = $eventsTotal > 0 ? round($errorsTotal / $eventsTotal, 6) : 0.0;
            $availabilityPercent = round((1 - $errorRate) * 100, 2);
            $avgDurationMs =
                $durationCount > 0 ? round($durationTotalMs / $durationCount, 2) : null;

            $analytics[(string) $domain] = [
                "events_total" => $eventsTotal,
                "errors_total" => $errorsTotal,
                "error_rate" => $errorRate,
                "availability_percent" => $availabilityPercent,
                "avg_duration_ms" => $avgDurationMs,
            ];
        }

        return $analytics;
    }

    /**
     * @param array<string, mixed> $summary
     * @return array<int, array<string, mixed>>
     */
    private function buildAlerts(array $summary): array
    {
        if (!(bool) config("observability.alerts_enabled", true)) {
            return [];
        }

        $domains = is_array($summary["domains"] ?? null) ? $summary["domains"] : [];
        $minEvents = max(1, (int) config("observability.alerts_min_events", 20));
        $threshold = (float) config("observability.alerts_error_rate_threshold", 0.2);
        $alerts = [];

        foreach ($domains as $domain => $domainSummary) {
            if (!is_array($domainSummary)) {
                continue;
            }

            $eventsTotal = (int) ($domainSummary["events_total"] ?? 0);
            $errorsTotal = (int) ($domainSummary["errors_total"] ?? 0);
            if ($eventsTotal < $minEvents || $eventsTotal <= 0) {
                continue;
            }

            $errorRate = $errorsTotal / $eventsTotal;
            if ($errorRate < $threshold) {
                continue;
            }

            $alerts[] = [
                "code" => "high_error_rate",
                "severity" => "warning",
                "domain" => (string) $domain,
                "message" => sprintf(
                    "High error rate for domain %s: %.2f%% (%d/%d)",
                    (string) $domain,
                    $errorRate * 100,
                    $errorsTotal,
                    $eventsTotal,
                ),
                "value" => $errorRate,
                "threshold" => $threshold,
                "events_total" => $eventsTotal,
                "errors_total" => $errorsTotal,
            ];
        }

        return $alerts;
    }
}
