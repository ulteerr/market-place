<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Services\ObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ObservabilityDomainContractTest extends TestCase
{
    use RefreshDatabase;

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
    public function new_domain_is_aggregated_without_core_service_changes(): void
    {
        $event = app(ObservabilityService::class)->recordEvent(
            "imports",
            "imports.job",
            "sync",
            "ok",
            "info",
            120,
            ["batch_id" => "b-1"],
        );

        $dashboard = app(ObservabilityService::class)->dashboard("imports");
        $domains = $dashboard["summary"]["domains"] ?? [];

        $this->assertSame("imports", $event["domain"] ?? null);
        $this->assertSame("imports.job", $event["component"] ?? null);
        $this->assertSame("sync", $event["event"] ?? null);
        $this->assertSame("ok", $event["status"] ?? null);
        $this->assertArrayHasKey("timestamp", $event);
        $this->assertArrayHasKey("request_id", $event);
        $this->assertSame("b-1", $event["meta"]["batch_id"] ?? null);

        $this->assertSame(1, $domains["imports"]["events_total"] ?? null);
        $this->assertSame(0, $domains["imports"]["errors_total"] ?? null);
        $this->assertSame(1, $domains["imports"]["events"]["sync"]["ok"] ?? null);
    }
}
