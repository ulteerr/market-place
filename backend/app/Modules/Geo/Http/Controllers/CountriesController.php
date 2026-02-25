<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Geo\Services\CountriesService;

final class CountriesController
{
    public function __construct(private readonly CountriesService $countriesService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->countriesService->list([
            "search" => (string) $request->query("search", ""),
        ]);

        return StatusResponseFactory::success($items);
    }
}
