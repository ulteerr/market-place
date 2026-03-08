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

final class ObservabilityDomainAdaptersTest extends TestCase
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
            "observability.alerts_error_rate_threshold" => 0.4,
        ]);
        Cache::store("array")->flush();

        $redisConnection = \Mockery::mock();
        $redisConnection->shouldReceive("exists")->andReturn(0);
        $redisConnection->shouldReceive("setex")->andReturnTrue();
        Redis::shouldReceive("connection")->andReturn($redisConnection);
    }

    #[Test]
    public function presence_and_auth_domains_are_visible_in_unified_dashboard(): void
    {
        User::factory()->create([
            "email" => "observer-domain@example.com",
            "password" => "password123",
        ]);

        $this->postJson("/api/auth/login", [
            "email" => "observer-domain@example.com",
            "password" => "password123",
        ])->assertOk();

        $this->postJson("/api/auth/login", [
            "email" => "observer-domain@example.com",
            "password" => "wrong-password",
        ])->assertStatus(422);

        $presenceUserAuth = $this->actingAsUser();
        $this->withHeaders($presenceUserAuth["headers"])
            ->postJson("/api/presence/heartbeat")
            ->assertOk();

        $dashboard = app(ObservabilityService::class)->dashboard();
        $domains = $dashboard["summary"]["domains"] ?? [];

        $this->assertSame(2, $domains["auth"]["events_total"] ?? null);
        $this->assertSame(1, $domains["presence"]["events_total"] ?? null);
        $this->assertSame(1, $domains["auth"]["events"]["login"]["ok"] ?? null);
        $this->assertSame(1, $domains["auth"]["events"]["login"]["error"] ?? null);
        $this->assertSame(1, $domains["presence"]["events"]["heartbeat"]["ok"] ?? null);
    }
}
