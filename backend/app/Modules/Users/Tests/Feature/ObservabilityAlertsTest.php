<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Services\ObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ObservabilityAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            "observability.cache_store" => "array",
            "observability.summary_key" => "test:observability:summary",
            "observability.incidents_key" => "test:observability:incidents",
            "observability.alerts_enabled" => true,
            "observability.alerts_min_events" => 2,
            "observability.alerts_error_rate_threshold" => 0.5,
        ]);
        Cache::store("array")->flush();

        $redisConnection = \Mockery::mock();
        $redisConnection->shouldReceive("exists")->andReturn(0);
        $redisConnection->shouldReceive("setex")->andReturnTrue();
        Redis::shouldReceive("connection")->andReturn($redisConnection);
    }

    #[Test]
    public function high_error_rate_alert_is_emitted_for_degraded_domain_only(): void
    {
        User::factory()->create([
            "email" => "observer-alert@example.com",
            "password" => "password123",
        ]);

        $this->postJson("/api/auth/login", [
            "email" => "observer-alert@example.com",
            "password" => "wrong-password",
        ])->assertStatus(422);

        $this->postJson("/api/auth/login", [
            "email" => "observer-alert@example.com",
            "password" => "wrong-password",
        ])->assertStatus(422);

        $this->postJson("/api/auth/login", [
            "email" => "observer-alert@example.com",
            "password" => "password123",
        ])->assertOk();

        $presenceUserAuth = $this->actingAsUser();
        $this->withHeaders($presenceUserAuth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk();
        $this->withHeaders($presenceUserAuth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk();

        $dashboard = app(ObservabilityService::class)->dashboard();
        $domains = $dashboard["summary"]["domains"] ?? [];
        $alerts = $dashboard["alerts"] ?? [];

        $this->assertSame(3, $domains["auth"]["events_total"] ?? null);
        $this->assertSame(2, $domains["auth"]["errors_total"] ?? null);
        $this->assertSame(2, $domains["presence"]["events_total"] ?? null);
        $this->assertCount(1, $alerts);
        $this->assertSame("high_error_rate", $alerts[0]["code"] ?? null);
        $this->assertSame("auth", $alerts[0]["domain"] ?? null);
        $this->assertSame(3, $alerts[0]["events_total"] ?? null);
        $this->assertSame(2, $alerts[0]["errors_total"] ?? null);
    }
}
