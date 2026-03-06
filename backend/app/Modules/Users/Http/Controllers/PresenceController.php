<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Services\ObservabilityService;
use Modules\Users\Services\PresenceService;

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

        $this->presenceService->heartbeat($user);
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
}
