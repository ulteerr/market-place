<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\Concerns\BuildsDictionaryFilters;
use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\CitiesService;

final class CitiesController
{
    use BuildsDictionaryFilters;

    public function __construct(private readonly CitiesService $citiesService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->citiesService->list(
            $this->dictionaryFilters($request, ["country_id", "region_id"]),
        );

        return StatusResponseFactory::success($items);
    }
}
