<?php

declare(strict_types=1);

namespace App\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;

final class StatusResponseFactory
{
    public static function ok(string $message): JsonResponse
    {
        return response()->json([
            'status'  => 'ok',
            'message' => $message,
        ]);
    }
}
