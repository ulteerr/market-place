<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOwnMeSettingsChannel
{
    public function handle(Request $request, Closure $next): Response
    {
        $channelName = trim((string) $request->input("channel_name", ""));
        $prefix = "private-me-settings.";

        if (!str_starts_with($channelName, $prefix)) {
            return $next($request);
        }

        $user = $request->user();
        $requestedUserId = substr($channelName, strlen($prefix));
        if ($user === null || !is_string($requestedUserId) || trim($requestedUserId) === "") {
            return $this->forbiddenResponse();
        }

        if ((string) $user->id !== $requestedUserId) {
            return $this->forbiddenResponse();
        }

        return $next($request);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json(
            [
                "status" => "error",
                "message" => "Forbidden",
                "errors" => null,
            ],
            403,
        );
    }
}
