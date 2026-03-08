<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use Modules\Users\Models\User;
use Modules\Users\Support\ChannelAuthorization;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChannelAuthorizationTest extends TestCase
{
    #[Test]
    public function owner_can_access_own_user_channel(): void
    {
        $user = User::factory()->make([
            "id" => "eb96eccb-4634-43f8-ad92-d22026e8d677",
        ]);

        $this->assertTrue(
            ChannelAuthorization::canAccessOwnUserChannel(
                $user,
                "eb96eccb-4634-43f8-ad92-d22026e8d677",
            ),
        );
    }

    #[Test]
    public function user_cannot_access_foreign_user_channel(): void
    {
        $user = User::factory()->make([
            "id" => "eb96eccb-4634-43f8-ad92-d22026e8d677",
        ]);

        $this->assertFalse(
            ChannelAuthorization::canAccessOwnUserChannel(
                $user,
                "7ceca8e6-11d1-42b4-9f00-ae3b19ecf798",
            ),
        );
    }
}
