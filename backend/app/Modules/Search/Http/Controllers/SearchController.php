<?php

declare(strict_types=1);

namespace Modules\Search\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Services\SearchService;

final class SearchController
{
    public function __construct(private readonly SearchService $searchService) {}

    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query("q", ""));
        $limit = max(1, min(10, (int) $request->integer("limit", 8)));

        return StatusResponseFactory::success($this->searchService->suggest($query, $limit));
    }

    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->query("q", ""));
        $perPage = max(1, min(48, (int) $request->integer("per_page", 12)));

        return StatusResponseFactory::success(
            $this->searchService->search($query, [
                "per_page" => $perPage,
                "page" => max(1, (int) $request->integer("page", 1)),
                "organization_id" => trim((string) $request->query("organization_id", "")),
                "root_category_id" => trim((string) $request->query("root_category_id", "")),
                "category_id" => trim((string) $request->query("category_id", "")),
                "city_id" => trim((string) $request->query("city_id", "")),
                "is_featured" => $request->query("is_featured"),
                "sort_by" => trim((string) $request->query("sort_by", "")),
                "sort_dir" => strtolower((string) $request->query("sort_dir", "desc")),
            ]),
        );
    }
}
