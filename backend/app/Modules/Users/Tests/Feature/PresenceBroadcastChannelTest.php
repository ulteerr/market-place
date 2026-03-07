<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PresenceBroadcastChannelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_authorize_presence_broadcast_channel(): void
    {
        $this->postJson("/broadcasting/auth", [
            "socket_id" => "123.456",
            "channel_name" => "private-users.presence",
        ])->assertUnauthorized();
    }

    #[Test]
    public function authenticated_user_can_authorize_presence_broadcast_channel(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->postJson("/broadcasting/auth", [
                "socket_id" => "123.456",
                "channel_name" => "private-users.presence",
            ])
            ->assertOk();
    }
}
