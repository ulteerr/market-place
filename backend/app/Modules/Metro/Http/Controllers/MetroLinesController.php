<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metro\Services\MetroLinesService;

final class MetroLinesController
{
    public function __construct(private readonly MetroLinesService $metroLinesService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->metroLinesService->list([
            "city_id" => (string) $request->query("city_id", ""),
            "search" => (string) $request->query("search", ""),
        ]);

        return StatusResponseFactory::success($items);
    }
}
