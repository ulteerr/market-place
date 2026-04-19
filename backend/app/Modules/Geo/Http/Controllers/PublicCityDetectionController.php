<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Http\Resources\CityResource;
use Modules\Geo\Services\PublicCityDetectionService;

final class PublicCityDetectionController
{
    public function __construct(
        private readonly PublicCityDetectionService $publicCityDetectionService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $result = $this->publicCityDetectionService->detect($request);

        if (!$result) {
            return StatusResponseFactory::error("No cities available for public detection.", 404);
        }

        return StatusResponseFactory::success([
            "city" => (new CityResource($result["city"]))->resolve(),
            "resolved_by" => $result["resolved_by"],
        ]);
    }
}
