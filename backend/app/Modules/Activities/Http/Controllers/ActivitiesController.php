<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Activities\Http\Responses\ActivityResponseFactory;
use Modules\Activities\Services\ActivitiesService;

final class ActivitiesController
{
    public function __construct(private readonly ActivitiesService $activitiesService) {}

    public function featured(Request $request): JsonResponse
    {
        $limit = max(1, min(24, (int) $request->integer("limit", 12)));
        $items = $this->activitiesService->featured($limit, [
            "city_id" => trim((string) $request->query("city_id", "")),
        ]);

        return ActivityResponseFactory::feedCollection($items);
    }

    public function feed(Request $request): JsonResponse
    {
        $limit = max(1, min(48, (int) $request->integer("limit", 20)));
        $payload = $this->activitiesService->feed(
            $limit,
            $this->normalizeQueryString($request->query("cursor")),
            [
                "search" => trim((string) $request->query("search", "")),
                "root_category_id" => trim((string) $request->query("root_category_id", "")),
                "organization_id" => trim((string) $request->query("organization_id", "")),
                "location_id" => trim((string) $request->query("location_id", "")),
                "category_id" => trim((string) $request->query("category_id", "")),
                "city_id" => trim((string) $request->query("city_id", "")),
                "is_featured" => $request->query("is_featured"),
                "sort_by" => trim((string) $request->query("sort_by", "created_at")),
                "sort_dir" => strtolower((string) $request->query("sort_dir", "desc")),
            ],
        );

        return ActivityResponseFactory::feed($payload["items"], $payload["next_cursor"]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer("per_page", 20)));
        $items = $this->activitiesService->paginate(
            $perPage,
            [],
            [
                "search" => trim((string) $request->query("search", "")),
                "status" => (string) $request->query("status", "published"),
                "is_featured" => $request->query("is_featured"),
                "root_category_id" => trim((string) $request->query("root_category_id", "")),
                "organization_id" => trim((string) $request->query("organization_id", "")),
                "location_id" => trim((string) $request->query("location_id", "")),
                "category_id" => trim((string) $request->query("category_id", "")),
                "city_id" => trim((string) $request->query("city_id", "")),
                "sort_by" => trim((string) $request->query("sort_by", "created_at")),
                "sort_dir" => strtolower((string) $request->query("sort_dir", "desc")),
            ],
        );

        return ActivityResponseFactory::paginated($items);
    }

    public function show(string $publicKey): JsonResponse
    {
        $activity = $this->activitiesService->findByPublicRouteKey($publicKey);
        if (!$activity) {
            abort(404, "Activity not found");
        }

        return ActivityResponseFactory::success($activity);
    }

    private function normalizeQueryString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === "" ? null : $value;
    }
}
