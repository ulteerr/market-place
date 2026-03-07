<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RealtimeObservabilityTest extends TestCase
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
    public function guest_cannot_report_realtime_observability_event(): void
    {
        $this->postJson("/api/admin/observability/realtime-event", [
            "event" => "websocket_connect_ok",
        ])->assertUnauthorized();
    }

    #[Test]
    public function admin_can_report_realtime_event_and_see_it_in_dashboard(): void
    {
        $auth = $this->actingAsUser();
        $adminRole = Role::factory()->admin()->create();
        $auth["user"]->roles()->sync([$adminRole->id]);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/observability/realtime-event", [
                "event" => "websocket_connect_ok",
                "meta" => [
                    "channel" => "users.presence",
                ],
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/observability?domain=realtime")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.summary.domains.realtime.events_total", 1)
            ->assertJsonPath("data.summary.domains.realtime.events.websocket_connect_ok.ok", 1);
    }
}
