<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use Modules\Users\Console\Commands\WatchPresenceExpiryCommand;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WatchPresenceExpiryCommandTest extends TestCase
{
    #[Test]
    public function it_extracts_user_id_from_presence_key(): void
    {
        config([
            "presence.key_prefix" => "presence:user:",
            "presence.key_suffix" => ":online",
        ]);

        $userId = WatchPresenceExpiryCommand::extractUserIdFromPresenceKey(
            "presence:user:eb96eccb-4634-43f8-ad92-d22026e8d677:online",
        );

        $this->assertSame("eb96eccb-4634-43f8-ad92-d22026e8d677", $userId);
    }

    #[Test]
    public function it_returns_null_for_non_presence_key(): void
    {
        config([
            "presence.key_prefix" => "presence:user:",
            "presence.key_suffix" => ":online",
        ]);

        $userId = WatchPresenceExpiryCommand::extractUserIdFromPresenceKey("foo:bar:baz");

        $this->assertNull($userId);
    }

    #[Test]
    public function it_extracts_user_id_from_prefixed_redis_key(): void
    {
        config([
            "database.redis.options.prefix" => "marketplace-database-",
            "presence.key_prefix" => "presence:user:",
            "presence.key_suffix" => ":online",
        ]);

        $userId = WatchPresenceExpiryCommand::extractUserIdFromPresenceKey(
            "marketplace-database-presence:user:eb96eccb-4634-43f8-ad92-d22026e8d677:online",
        );

        $this->assertSame("eb96eccb-4634-43f8-ad92-d22026e8d677", $userId);
    }
}
