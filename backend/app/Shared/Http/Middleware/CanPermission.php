<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CanPermission
{
    public function handle(Request $request, Closure $next, string ...$codes): Response
    {
        $user = $request->user();

        if (!$user || empty($codes) || !$user->hasAnyPermission($codes)) {
            return response()->json(
                [
                    "status" => "error",
                    "message" => "Forbidden",
                ],
                403,
            );
        }

        return $next($request);
    }
}
