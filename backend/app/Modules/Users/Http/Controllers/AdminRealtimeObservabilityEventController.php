<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Services\ObservabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AdminRealtimeObservabilityEventController extends Controller
{
    private const ALLOWED_EVENTS = [
        "websocket_connect_ok",
        "websocket_connect_error",
        "websocket_subscribe_ok",
        "websocket_subscribe_error",
        "settings_realtime_fallback_enabled",
        "settings_realtime_fallback_disabled",
        "broadcast_dispatch_ok",
        "broadcast_dispatch_error",
    ];

    public function __construct(private readonly ObservabilityService $observabilityService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $event = trim((string) $request->input("event", ""));
        if (!in_array($event, self::ALLOWED_EVENTS, true)) {
            return StatusResponseFactory::error("Invalid realtime event", 422);
        }

        $status = trim((string) $request->input("status", ""));
        if ($status === "") {
            $status = str_ends_with($event, "_error") ? "error" : "ok";
        }

        $severity = trim((string) $request->input("severity", ""));
        if ($severity === "") {
            $severity = $status === "ok" ? "info" : "warning";
        }

        $meta = $request->input("meta", []);
        if (!is_array($meta)) {
            $meta = [];
        }

        $this->observabilityService->recordEvent(
            "realtime",
            "realtime.client",
            $event,
            $status,
            $severity,
            null,
            $meta,
        );

        return StatusResponseFactory::ok("Realtime observability event accepted");
    }
}
