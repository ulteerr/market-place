<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\Concerns\BuildsDictionaryFilters;
use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\RegionsService;

final class RegionsController
{
    use BuildsDictionaryFilters;

    public function __construct(private readonly RegionsService $regionsService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->regionsService->list($this->dictionaryFilters($request, ["country_id"]));

        return StatusResponseFactory::success($items);
    }
}
