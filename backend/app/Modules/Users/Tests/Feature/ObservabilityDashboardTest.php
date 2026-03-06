<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Shared\Services\ObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ObservabilityDashboardTest extends TestCase
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
    public function guest_cannot_access_observability_dashboard(): void
    {
        $this->getJson("/api/admin/observability")->assertUnauthorized();
    }

    #[Test]
    public function admin_can_view_observability_dashboard_data(): void
    {
        $auth = $this->actingAsUser();
        $adminRole = Role::factory()->admin()->create();
        $auth["user"]->roles()->sync([$adminRole->id]);

        $service = app(ObservabilityService::class);
        $service->recordEvent("presence", "presence.controller", "heartbeat", "ok", "info", 20);
        $service->recordEvent(
            "presence",
            "presence.service",
            "redis_operation",
            "error",
            "warning",
        );
        $service->recordEvent("auth", "auth.controller", "login", "ok", "info", 10);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/observability?domain=presence&limit=10")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.summary.domains.presence.events_total", 2)
            ->assertJsonPath("data.summary.domains.presence.errors_total", 1)
            ->assertJsonPath("data.incidents.0.domain", "presence");
    }
}
