<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Metro\Http\Resources\MetroLineResource;
use Modules\Metro\Models\MetroLine;

final class MetroLineResponseFactory
{
    public static function success(MetroLine $line, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::success(new MetroLineResource($line), $status);
    }

    public static function paginated(LengthAwarePaginator $lines, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::paginated(
            $lines,
            MetroLineResource::collection($lines->getCollection())->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        MetroLine $line,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::successWithMessage(
            $message,
            (new MetroLineResource($line))->resolve(),
            $status,
        );
    }
}
