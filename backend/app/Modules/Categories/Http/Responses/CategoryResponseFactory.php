<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Modules\Categories\Http\Resources\CategoryResource;
use Modules\Categories\Models\Category;

final class CategoryResponseFactory
{
    public static function success(Category $category, int $status = 200): JsonResponse
    {
        $category->loadMissing(["parent", "children"]);

        return StatusResponseFactory::success(new CategoryResource($category), $status);
    }

    public static function collection(Collection $categories, int $status = 200): JsonResponse
    {
        if (method_exists($categories, "loadMissing")) {
            $categories->loadMissing(["parent", "children"]);
        }

        return StatusResponseFactory::success(
            CategoryResource::collection($categories)->resolve(),
            $status,
        );
    }

    public static function paginated(
        LengthAwarePaginator $categories,
        int $status = 200,
    ): JsonResponse {
        $collection = $categories->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing(["parent"]);
        }

        return StatusResponseFactory::paginated(
            $categories,
            CategoryResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Category $category,
        int $status = 200,
    ): JsonResponse {
        $category->loadMissing(["parent", "children"]);

        return StatusResponseFactory::successWithMessage(
            $message,
            (new CategoryResource($category))->resolve(),
            $status,
        );
    }
}
