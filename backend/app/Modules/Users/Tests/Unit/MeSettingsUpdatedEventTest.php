<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Events\MeSettingsUpdated;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MeSettingsUpdatedEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function me_settings_updated_event_exposes_expected_broadcast_contract(): void
    {
        $user = User::factory()->create([
            "settings" => [
                "locale" => "en",
                "theme" => "dark",
            ],
        ]);

        $event = new MeSettingsUpdated($user->fresh());
        $payload = $event->broadcastWith();

        $this->assertSame("me.settings.updated", $event->broadcastAs());
        $this->assertStringContainsString("me-settings.{$user->id}", $event->broadcastOn()->name);
        $this->assertSame((string) $user->id, $payload["user_id"] ?? null);
        $this->assertSame("dark", $payload["settings"]["theme"] ?? null);
        $this->assertIsString($payload["updated_at"] ?? null);
        $this->assertIsInt($payload["version"] ?? null);
    }
}
