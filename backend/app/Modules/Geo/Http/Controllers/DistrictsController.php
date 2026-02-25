<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\DistrictsService;

final class DistrictsController
{
    public function __construct(private readonly DistrictsService $districtsService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->districtsService->list([
            "city_id" => (string) $request->query("city_id", ""),
            "search" => (string) $request->query("search", ""),
        ]);

        return StatusResponseFactory::success($items);
    }
}
