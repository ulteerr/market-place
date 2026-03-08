<?php

declare(strict_types=1);

namespace Modules\Users\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Models\User;

final class MeSettingsUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly User $user) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("me-settings.{$this->user->id}");
    }

    public function broadcastAs(): string
    {
        return "me.settings.updated";
    }

    /**
     * @return array{
     *     user_id: string,
     *     settings: array<string, mixed>,
     *     updated_at: ?string,
     *     version: int
     * }
     */
    public function broadcastWith(): array
    {
        $settings = is_array($this->user->settings) ? $this->user->settings : [];

        return [
            "user_id" => (string) $this->user->id,
            "settings" => $settings,
            "updated_at" => $this->user->updated_at?->toIso8601String(),
            "version" => $this->user->updated_at?->getTimestamp() ?? now()->getTimestamp(),
        ];
    }
}
