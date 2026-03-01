<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Geo\Http\Resources\RegionResource;
use Modules\Geo\Models\Region;

final class RegionResponseFactory
{
    public static function success(Region $region, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::success(new RegionResource($region), $status);
    }

    public static function paginated(LengthAwarePaginator $regions, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::paginated(
            $regions,
            RegionResource::collection($regions->getCollection())->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Region $region,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::successWithMessage(
            $message,
            (new RegionResource($region))->resolve(),
            $status,
        );
    }
}
