<?php

declare(strict_types=1);

namespace Modules\Users\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Models\User;

final class UserLastSeenUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly User $user) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.presence");
    }

    public function broadcastAs(): string
    {
        return "users.last_seen_updated";
    }

    /**
     * @return array{user_id: string, is_online: bool, last_seen_at: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            "user_id" => (string) $this->user->id,
            "is_online" => true,
            "last_seen_at" => $this->user->last_seen_at?->toIso8601String(),
        ];
    }
}
