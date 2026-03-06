<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthObservabilityTest extends TestCase
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
    }

    #[Test]
    public function login_success_and_failure_are_recorded_in_auth_domain(): void
    {
        User::factory()->create([
            "email" => "observer@example.com",
            "password" => "password123",
        ]);

        $this->postJson("/api/auth/login", [
            "email" => "observer@example.com",
            "password" => "password123",
        ])->assertOk();

        $this->postJson("/api/auth/login", [
            "email" => "observer@example.com",
            "password" => "wrong-password",
        ])->assertStatus(422);

        $admin = $this->actingAsUser();
        $adminRole = Role::factory()->admin()->create();
        $admin["user"]->roles()->sync([$adminRole->id]);

        $this->withHeaders($admin["headers"])
            ->getJson("/api/admin/observability?domain=auth")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.summary.domains.auth.events_total", 2)
            ->assertJsonPath("data.summary.domains.auth.errors_total", 1)
            ->assertJsonPath("data.summary.domains.auth.events.login.ok", 1)
            ->assertJsonPath("data.summary.domains.auth.events.login.error", 1)
            ->assertJsonPath("data.alerts.0.code", "high_error_rate");
    }
}
