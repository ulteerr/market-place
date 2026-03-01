<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Geo\Http\Resources\CityResource;
use Modules\Geo\Models\City;

final class CityResponseFactory
{
    public static function success(City $city, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::success(new CityResource($city), $status);
    }

    public static function paginated(LengthAwarePaginator $cities, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::paginated(
            $cities,
            CityResource::collection($cities->getCollection())->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        City $city,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::successWithMessage(
            $message,
            (new CityResource($city))->resolve(),
            $status,
        );
    }
}
