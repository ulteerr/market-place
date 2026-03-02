<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\Concerns\BuildsDictionaryFilters;
use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\CountriesService;

final class CountriesController
{
    use BuildsDictionaryFilters;

    public function __construct(private readonly CountriesService $countriesService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->countriesService->list($this->dictionaryFilters($request));

        return StatusResponseFactory::success($items);
    }
}
