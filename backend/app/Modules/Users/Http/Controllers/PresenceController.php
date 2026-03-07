<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Services\ObservabilityService;
use Modules\Users\Events\UserLastSeenUpdated;
use Modules\Users\Events\UserWentOnline;
use Modules\Users\Services\PresenceService;
use Throwable;

final class PresenceController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService,
        private readonly ObservabilityService $observabilityService,
    ) {}

    public function heartbeat(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $user = $request->user();
        if ($user === null) {
            $this->observabilityService->recordEvent(
                "presence",
                "presence.controller",
                "heartbeat",
                "unauthorized",
                "warning",
                (int) ((microtime(true) - $startedAt) * 1000),
            );

            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $heartbeatResult = $this->presenceService->heartbeat($user);

        if (($heartbeatResult["became_online"] ?? false) === true) {
            $this->dispatchPresenceBroadcastEvent(new UserWentOnline($user));
        }

        if (($heartbeatResult["last_seen_updated"] ?? false) === true) {
            $this->dispatchPresenceBroadcastEvent(new UserLastSeenUpdated($user));
        }

        $this->observabilityService->recordEvent(
            "presence",
            "presence.controller",
            "heartbeat",
            "ok",
            "info",
            (int) ((microtime(true) - $startedAt) * 1000),
            ["user_id" => (string) $user->id],
        );

        return StatusResponseFactory::ok("Heartbeat accepted");
    }

    private function dispatchPresenceBroadcastEvent(object $event): void
    {
        try {
            event($event);
            $this->observabilityService->recordEvent(
                "realtime",
                "realtime.broadcast",
                "broadcast_dispatch_ok",
                "ok",
                "info",
                null,
                ["event" => get_class($event)],
            );
        } catch (Throwable $exception) {
            $this->observabilityService->recordEvent(
                "realtime",
                "realtime.broadcast",
                "broadcast_dispatch_error",
                "error",
                "warning",
                null,
                [
                    "event" => get_class($event),
                    "error" => $exception->getMessage(),
                ],
            );
        }
    }
}
