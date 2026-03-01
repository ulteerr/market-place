<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Metro\Http\Resources\MetroStationResource;
use Modules\Metro\Models\MetroStation;

final class MetroStationResponseFactory
{
    public static function success(MetroStation $station, int $status = 200): JsonResponse
    {
        $station->loadMissing(["metroLine:id,name,color", "city:id,name"]);

        return StatusResponseFactory::success(new MetroStationResource($station), $status);
    }

    public static function paginated(
        LengthAwarePaginator $stations,
        int $status = 200,
    ): JsonResponse {
        $collection = $stations->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing(["metroLine:id,name,color", "city:id,name"]);
        }

        return StatusResponseFactory::paginated(
            $stations,
            MetroStationResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        MetroStation $station,
        int $status = 200,
    ): JsonResponse {
        $station->loadMissing(["metroLine:id,name,color", "city:id,name"]);

        return StatusResponseFactory::successWithMessage(
            $message,
            (new MetroStationResource($station))->resolve(),
            $status,
        );
    }
}
