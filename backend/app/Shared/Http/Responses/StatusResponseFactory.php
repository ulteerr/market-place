<?php

declare(strict_types=1);

namespace App\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;

final class StatusResponseFactory
{
    public static function ok(
        string $message,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json(
            [
                'status'  => 'ok',
                'message' => $message,
            ],
            $statusCode
        );
    }

    public static function success(
        mixed $data,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json(
            [
                'status' => 'ok',
                'data'   => $data,
            ],
            $statusCode
        );
    }

    public static function successWithMessage(
        string $message,
        mixed $data = null,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json(
            [
                'status'  => 'ok',
                'message' => $message,
                'data'    => $data,
            ],
            $statusCode
        );
    }

    public static function error(
        string $message,
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json(
            [
                'status'  => 'error',
                'message' => $message,
                'errors'  => $errors,
            ],
            $statusCode
        );
    }
}
