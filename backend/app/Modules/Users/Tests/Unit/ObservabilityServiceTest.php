<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use App\Shared\Services\ObservabilityService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ObservabilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            "observability.cache_store" => "array",
            "observability.summary_key" => "test:observability:summary",
            "observability.incidents_key" => "test:observability:incidents",
        ]);
        Cache::store("array")->flush();
    }

    #[Test]
    public function it_records_event_with_stable_format_and_masks_sensitive_meta(): void
    {
        $payload = app(ObservabilityService::class)->recordEvent(
            "presence",
            "presence.controller",
            "heartbeat",
            "error",
            "warning",
            145,
            [
                "token" => "secret-token",
                "nested" => [
                    "password" => "secret-password",
                ],
                "safe" => "ok",
            ],
        );

        $this->assertSame("presence", $payload["domain"] ?? null);
        $this->assertSame("presence.controller", $payload["component"] ?? null);
        $this->assertSame("heartbeat", $payload["event"] ?? null);
        $this->assertSame("error", $payload["status"] ?? null);
        $this->assertSame("warning", $payload["severity"] ?? null);
        $this->assertSame(145, $payload["duration_ms"] ?? null);
        $this->assertSame("[masked]", $payload["meta"]["token"] ?? null);
        $this->assertSame("[masked]", $payload["meta"]["nested"]["password"] ?? null);
        $this->assertSame("ok", $payload["meta"]["safe"] ?? null);
    }

    #[Test]
    public function it_aggregates_metrics_by_domain_and_event_status(): void
    {
        $service = app(ObservabilityService::class);

        $service->recordEvent("presence", "presence.controller", "heartbeat", "ok", "info", 30);
        $service->recordEvent(
            "presence",
            "presence.controller",
            "heartbeat",
            "error",
            "warning",
            40,
        );
        $service->recordEvent("auth", "auth.controller", "login", "ok", "info", 10);

        $dashboard = $service->dashboard();
        $domains = $dashboard["summary"]["domains"] ?? [];

        $this->assertSame(2, $domains["presence"]["events_total"] ?? null);
        $this->assertSame(1, $domains["presence"]["errors_total"] ?? null);
        $this->assertSame(1, $domains["presence"]["events"]["heartbeat"]["ok"] ?? null);
        $this->assertSame(1, $domains["presence"]["events"]["heartbeat"]["error"] ?? null);

        $this->assertSame(1, $domains["auth"]["events_total"] ?? null);
        $this->assertSame(0, $domains["auth"]["errors_total"] ?? null);
        $this->assertSame(1, $domains["auth"]["events"]["login"]["ok"] ?? null);
    }

    #[Test]
    public function it_keeps_incidents_masked_and_filterable_by_domain(): void
    {
        $service = app(ObservabilityService::class);

        $service->recordEvent(
            "presence",
            "presence.service",
            "redis_operation",
            "error",
            "warning",
            null,
            ["authorization" => "Bearer top-secret"],
        );
        $service->recordEvent("auth", "auth.controller", "login", "ok", "info", 12);

        $presenceDashboard = $service->dashboard("presence");
        $incidents = $presenceDashboard["incidents"] ?? [];

        $this->assertCount(1, $incidents);
        $this->assertSame("presence", $incidents[0]["domain"] ?? null);
        $this->assertSame("[masked]", $incidents[0]["meta"]["authorization"] ?? null);
    }

    #[Test]
    public function it_builds_high_error_rate_alerts(): void
    {
        config([
            "observability.alerts_enabled" => true,
            "observability.alerts_min_events" => 2,
            "observability.alerts_error_rate_threshold" => 0.4,
        ]);

        $service = app(ObservabilityService::class);
        $service->recordEvent("presence", "presence.controller", "heartbeat", "ok", "info");
        $service->recordEvent("presence", "presence.controller", "heartbeat", "error", "warning");
        $service->recordEvent("presence", "presence.controller", "heartbeat", "error", "warning");

        $dashboard = $service->dashboard();
        $alerts = $dashboard["alerts"] ?? [];

        $this->assertCount(1, $alerts);
        $this->assertSame("high_error_rate", $alerts[0]["code"] ?? null);
        $this->assertSame("presence", $alerts[0]["domain"] ?? null);
        $this->assertSame(3, $alerts[0]["events_total"] ?? null);
        $this->assertSame(2, $alerts[0]["errors_total"] ?? null);
    }
}
