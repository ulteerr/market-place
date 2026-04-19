<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Http\Resources\CityResource;
use Modules\Geo\Services\CitiesService;

final class PublicCitiesController
{
    public function __construct(private readonly CitiesService $citiesService) {}

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query("search", ""));
        $limit = max(1, min((int) $request->query("limit", 20), 50));
        $items = $this->citiesService->publicOptions($search, $limit);

        return StatusResponseFactory::success(CityResource::collection($items)->resolve());
    }
}
