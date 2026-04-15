<?php

declare(strict_types=1);

namespace Modules\Search\Indexing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;

final class ActivitySearchSyncService
{
    public function __construct(private readonly ActivitySearchIndexer $indexer) {}

    public function syncActivity(Activity $activity): bool
    {
        return $this->indexer->sync($activity);
    }

    public function deleteActivity(string $activityId): bool
    {
        return $this->indexer->delete($activityId);
    }

    /**
     * @param iterable<string> $activityIds
     */
    public function syncByActivityIds(iterable $activityIds): bool
    {
        $ids = collect($activityIds)
            ->map(fn($id) => trim((string) $id))
            ->filter(fn(string $id) => $id !== "")
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return true;
        }

        $activities = Activity::query()
            ->whereIn("id", $ids->all())
            ->with([
                "organization:id,name",
                "location:id,city_id,address",
                "location.city:id,name",
                "primaryCategory:id,name,slug,parent_id",
                "primaryCategory.parent:id,name,slug",
            ])
            ->get();

        $existingIds = $activities->pluck("id")->map(fn($id) => (string) $id)->all();
        $deletedIds = $ids->diff($existingIds)->values()->all();

        if ($activities->isNotEmpty()) {
            $this->indexer->syncMany($activities);
        }

        if ($deletedIds !== []) {
            $this->indexer->deleteMany($deletedIds);
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function activityIdsForOrganization(string $organizationId): array
    {
        return Activity::query()
            ->where("organization_id", $organizationId)
            ->pluck("id")
            ->map(fn($id) => (string) $id)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activityIdsForLocation(string $locationId): array
    {
        return Activity::query()
            ->where("location_id", $locationId)
            ->pluck("id")
            ->map(fn($id) => (string) $id)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activityIdsForCity(string $cityId): array
    {
        return Activity::query()
            ->join(
                "organization_locations",
                "organization_locations.id",
                "=",
                "activities.location_id",
            )
            ->where("organization_locations.city_id", $cityId)
            ->distinct()
            ->pluck("activities.id")
            ->map(fn($id) => (string) $id)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activityIdsForCategory(string $categoryId): array
    {
        return DB::table("activity_categories")
            ->where("category_id", $categoryId)
            ->distinct()
            ->pluck("activity_id")
            ->map(fn($id) => (string) $id)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activityIdsForRootCategory(string $rootCategoryId): array
    {
        return DB::table("activity_categories")
            ->join("categories", "categories.id", "=", "activity_categories.category_id")
            ->where("categories.parent_id", $rootCategoryId)
            ->distinct()
            ->pluck("activity_categories.activity_id")
            ->map(fn($id) => (string) $id)
            ->all();
    }
}
