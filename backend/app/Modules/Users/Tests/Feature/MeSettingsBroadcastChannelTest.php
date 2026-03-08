<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MeSettingsBroadcastChannelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_authorize_me_settings_broadcast_channel(): void
    {
        $user = User::factory()->create();

        $this->postJson("/broadcasting/auth", [
            "socket_id" => "123.456",
            "channel_name" => "private-me-settings.{$user->id}",
        ])->assertUnauthorized();
    }

    #[Test]
    public function authenticated_user_can_authorize_own_me_settings_channel(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->postJson("/broadcasting/auth", [
                "socket_id" => "123.456",
                "channel_name" => "private-me-settings.{$auth["user"]->id}",
            ])
            ->assertOk();
    }

    #[Test]
    public function authenticated_user_cannot_authorize_other_user_me_settings_channel(): void
    {
        $auth = $this->actingAsUser();
        $other = User::factory()->create();

        $this->withHeaders($auth["headers"])
            ->postJson("/broadcasting/auth", [
                "socket_id" => "123.456",
                "channel_name" => "private-me-settings.{$other->id}",
            ])
            ->assertForbidden();
    }
}
