<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\RegionsService;

final class RegionsController
{
    public function __construct(private readonly RegionsService $regionsService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->regionsService->list([
            "country_id" => (string) $request->query("country_id", ""),
            "search" => (string) $request->query("search", ""),
        ]);

        return StatusResponseFactory::success($items);
    }
}
