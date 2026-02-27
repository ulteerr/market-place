<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metro\Services\MetroStationsService;

final class MetroStationsController
{
    public function __construct(private readonly MetroStationsService $metroStationsService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->metroStationsService->list([
            "city_id" => (string) $request->query("city_id", ""),
            "metro_line_id" => (string) $request->query("metro_line_id", ""),
            "search" => (string) $request->query("search", ""),
        ]);

        return StatusResponseFactory::success($items);
    }
}
