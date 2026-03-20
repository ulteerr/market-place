<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Categories\Http\Responses\CategoryResponseFactory;
use Modules\Categories\Services\CategoriesService;

final class CategoriesController
{
    public function __construct(private readonly CategoriesService $categoriesService) {}

    public function roots(Request $request): JsonResponse
    {
        $items = $this->categoriesService->getRoots($this->onlyActive($request));

        return CategoryResponseFactory::collection($items);
    }

    public function tree(Request $request): JsonResponse
    {
        $items = $this->categoriesService->getTree($this->onlyActive($request));

        return CategoryResponseFactory::collection($items);
    }

    public function children(string $parentId, Request $request): JsonResponse
    {
        $items = $this->categoriesService->getChildren($parentId, $this->onlyActive($request));

        return CategoryResponseFactory::collection($items);
    }

    private function onlyActive(Request $request): bool
    {
        return filter_var($request->query("only_active", true), FILTER_VALIDATE_BOOL);
    }
}
