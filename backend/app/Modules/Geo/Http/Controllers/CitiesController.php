<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\CitiesService;

final class CitiesController
{
    public function __construct(private readonly CitiesService $citiesService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->citiesService->list([
            "country_id" => (string) $request->query("country_id", ""),
            "region_id" => (string) $request->query("region_id", ""),
            "search" => (string) $request->query("search", ""),
        ]);

        return StatusResponseFactory::success($items);
    }
}
