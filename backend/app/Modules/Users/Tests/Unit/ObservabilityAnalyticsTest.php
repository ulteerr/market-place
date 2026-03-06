<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use App\Shared\Services\ObservabilityService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ObservabilityAnalyticsTest extends TestCase
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
    public function it_calculates_domain_error_rate_availability_and_average_duration(): void
    {
        $service = app(ObservabilityService::class);

        $service->recordEvent("presence", "presence.controller", "heartbeat", "ok", "info", 40);
        $service->recordEvent(
            "presence",
            "presence.controller",
            "heartbeat",
            "error",
            "warning",
            80,
        );
        $service->recordEvent("presence", "presence.controller", "heartbeat", "ok", "info", 30);

        $dashboard = $service->dashboard("presence");
        $analytics = $dashboard["analytics"]["presence"] ?? null;

        $this->assertIsArray($analytics);
        $this->assertSame(3, $analytics["events_total"] ?? null);
        $this->assertSame(1, $analytics["errors_total"] ?? null);
        $this->assertSame(0.333333, $analytics["error_rate"] ?? null);
        $this->assertSame(66.67, $analytics["availability_percent"] ?? null);
        $this->assertSame(50.0, $analytics["avg_duration_ms"] ?? null);
    }
}
