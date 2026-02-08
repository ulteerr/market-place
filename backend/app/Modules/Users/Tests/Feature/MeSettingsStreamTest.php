<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MeSettingsStreamTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_open_settings_stream(): void
    {
        $auth = $this->actingAsUser();

        $response = $this
            ->withHeaders($auth['headers'])
            ->get('/api/me/settings/stream');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8')
            ->assertHeader('X-Accel-Buffering', 'no')
            ->assertStreamed();
    }

    #[Test]
    public function guest_cannot_open_settings_stream(): void
    {
        $this
            ->getJson('/api/me/settings/stream')
            ->assertUnauthorized();
    }
}
