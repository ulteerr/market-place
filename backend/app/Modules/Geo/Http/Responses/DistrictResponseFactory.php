<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Geo\Http\Resources\DistrictResource;
use Modules\Geo\Models\District;

final class DistrictResponseFactory
{
    public static function success(District $district, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::success(new DistrictResource($district), $status);
    }

    public static function paginated(
        LengthAwarePaginator $districts,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::paginated(
            $districts,
            DistrictResource::collection($districts->getCollection())->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        District $district,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::successWithMessage(
            $message,
            (new DistrictResource($district))->resolve(),
            $status,
        );
    }
}
