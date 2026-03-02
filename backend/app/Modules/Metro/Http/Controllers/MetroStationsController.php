<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Controllers;

use App\Shared\Http\Controllers\Concerns\BuildsDictionaryFilters;
use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metro\Services\MetroStationsService;

final class MetroStationsController
{
    use BuildsDictionaryFilters;

    public function __construct(private readonly MetroStationsService $metroStationsService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->metroStationsService->list(
            $this->dictionaryFilters($request, ["city_id", "metro_line_id"]),
        );

        return StatusResponseFactory::success($items);
    }
}
