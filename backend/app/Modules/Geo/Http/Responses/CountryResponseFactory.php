<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Geo\Http\Resources\CountryResource;
use Modules\Geo\Models\Country;

final class CountryResponseFactory
{
    public static function success(Country $country, int $status = 200): JsonResponse
    {
        return StatusResponseFactory::success(new CountryResource($country), $status);
    }

    public static function paginated(
        LengthAwarePaginator $countries,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::paginated(
            $countries,
            CountryResource::collection($countries->getCollection())->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Country $country,
        int $status = 200,
    ): JsonResponse {
        return StatusResponseFactory::successWithMessage(
            $message,
            (new CountryResource($country))->resolve(),
            $status,
        );
    }
}
