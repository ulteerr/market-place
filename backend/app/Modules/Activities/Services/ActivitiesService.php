<?php

declare(strict_types=1);

namespace Modules\Activities\Services;

use App\Shared\Support\Transliteration;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\ActivitySchedule;
use Modules\Activities\Repositories\ActivitiesRepositoryInterface;
use Modules\Categories\Models\Category;

final class ActivitiesService
{
    use HasDictionaryCrudOperations;

    public function __construct(
        private readonly ActivitiesRepositoryInterface $repository,
        private readonly ActivityMediaService $activityMediaService,
    ) {}

    protected function entityNotFoundMessage(): string
    {
        return "Activity not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof Activity);
        $this->activityMediaService->purge($entity);
        $this->repository->delete($entity);
    }

    public function createActivity(array $data): Activity
    {
        return DB::transaction(function () use ($data): Activity {
            $categoryId = $this->requirePrimaryCategoryId($data);
            $attributes = $this->extractActivityAttributes($data);

            $activity = $this->repository->create($attributes);

            $this->syncPrimaryCategory($activity, $categoryId);
            $this->syncSchedules($activity, $data["schedules"] ?? []);
            $this->syncMedia($activity, $data);

            return $this->repository->findById((string) $activity->id) ?? $activity;
        });
    }

    public function create(array $data): Activity
    {
        return $this->createActivity($data);
    }

    public function updateActivity(Activity $activity, array $data): Activity
    {
        return DB::transaction(function () use ($activity, $data): Activity {
            $attributes = $this->extractActivityAttributes($data);

            if ($attributes !== []) {
                $activity = $this->repository->update($activity, $attributes);
            }

            if (array_key_exists("category_id", $data)) {
                $categoryId = $this->normalizeOptionalString($data["category_id"] ?? null);
                if ($categoryId === null) {
                    throw ValidationException::withMessages([
                        "category_id" => "Primary category is required.",
                    ]);
                }

                $this->syncPrimaryCategory($activity, $categoryId);
            }

            if (array_key_exists("schedules", $data)) {
                $this->syncSchedules($activity, $data["schedules"] ?? []);
            }

            $this->syncMedia($activity, $data);

            return $this->repository->findById((string) $activity->id) ?? $activity;
        });
    }

    public function update(string $id, array $data): Activity
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof Activity);

        return $this->updateActivity($entity, $data);
    }

    public function getActivityById(string $id): ?Activity
    {
        return $this->repository->findById($id);
    }

    public function findById(string $id): ?Activity
    {
        return $this->getActivityById($id);
    }

    public function findByPublicRouteKey(string $publicKey): ?Activity
    {
        return $this->repository->findByPublicRouteKey($publicKey);
    }

    public function featured(int $limit = 12): Collection
    {
        return $this->repository->featured($limit);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: Collection<int, Activity>, next_cursor: ?string}
     */
    public function feed(int $limit = 20, ?string $cursor = null, array $filters = []): array
    {
        return $this->repository->feed($limit, $cursor, $filters);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $with, $filters);
    }

    public function previewSlug(string $name, ?string $ignoreId = null): string
    {
        $baseSlug = Transliteration::slug($name);
        if ($baseSlug === "") {
            $baseSlug = "activity";
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = sprintf("%s-%d", $baseSlug, $suffix);
            $suffix += 1;
        }

        return $slug;
    }

    public function delete(Activity $activity): bool
    {
        return $this->repository->delete($activity);
    }

    /**
     * @param array<int, array<string, mixed>> $schedules
     */
    private function syncSchedules(Activity $activity, mixed $schedules): void
    {
        if (!is_array($schedules)) {
            return;
        }

        $activity->schedules()->delete();

        foreach ($schedules as $row) {
            if (!is_array($row)) {
                continue;
            }

            $dayOfWeek = isset($row["day_of_week"]) ? (int) $row["day_of_week"] : 0;
            $startTime = trim((string) ($row["start_time"] ?? ""));
            $endTime = trim((string) ($row["end_time"] ?? ""));

            if ($dayOfWeek < 1 || $dayOfWeek > 7 || $startTime === "" || $endTime === "") {
                continue;
            }

            ActivitySchedule::query()->create([
                "activity_id" => (string) $activity->id,
                "day_of_week" => $dayOfWeek,
                "start_time" => $startTime,
                "end_time" => $endTime,
            ]);
        }
    }

    private function syncPrimaryCategory(Activity $activity, string $categoryId): void
    {
        $category = Category::query()
            ->select(["id", "parent_id"])
            ->find($categoryId);
        if (!$category instanceof Category) {
            throw ValidationException::withMessages([
                "category_id" => "Category not found.",
            ]);
        }

        if ($category->parent_id === null) {
            throw ValidationException::withMessages([
                "category_id" => "Activity must be assigned to a leaf category.",
            ]);
        }

        $parent = Category::query()
            ->select(["id", "parent_id"])
            ->find((string) $category->parent_id);
        if (!$parent instanceof Category || $parent->parent_id !== null) {
            throw ValidationException::withMessages([
                "category_id" => "Activity category must be a leaf under a root category.",
            ]);
        }

        $activity->primaryCategory()->sync([$categoryId]);
    }

    private function syncMedia(Activity $activity, array $data): void
    {
        $coverDelete = (bool) ($data["cover_delete"] ?? false);
        if ($coverDelete) {
            $this->activityMediaService->removeCover($activity);
        }

        $cover = $data["cover"] ?? null;
        if ($cover instanceof UploadedFile) {
            $this->activityMediaService->attachCover($activity, $cover);
        }

        $galleryDeleteIds = $data["gallery_delete_ids"] ?? [];
        if (is_array($galleryDeleteIds)) {
            foreach ($galleryDeleteIds as $fileId) {
                $fileId = $this->normalizeOptionalString($fileId);
                if ($fileId === null) {
                    continue;
                }

                $this->activityMediaService->removeGalleryItem($activity, $fileId);
            }
        }

        $gallery = $data["gallery"] ?? [];
        if (is_array($gallery)) {
            foreach ($gallery as $item) {
                if (!$item instanceof UploadedFile) {
                    continue;
                }

                $this->activityMediaService->attachGalleryItem($activity, $item);
            }
        }

        $galleryOrderIds = $data["gallery_order_ids"] ?? null;
        if (is_array($galleryOrderIds)) {
            $this->activityMediaService->reorderGallery(
                $activity,
                array_values(
                    array_filter(
                        array_map(
                            fn($id): ?string => $this->normalizeOptionalString($id),
                            $galleryOrderIds,
                        ),
                    ),
                ),
            );
        }
    }

    private function requirePrimaryCategoryId(array $data): string
    {
        $categoryId = $this->normalizeOptionalString($data["category_id"] ?? null);
        if ($categoryId === null) {
            throw ValidationException::withMessages([
                "category_id" => "Primary category is required.",
            ]);
        }

        return $categoryId;
    }

    private function extractActivityAttributes(array $data): array
    {
        return array_intersect_key(
            $data,
            array_flip([
                "organization_id",
                "location_id",
                "name",
                "slug",
                "short_description",
                "description",
                "min_age",
                "max_age",
                "capacity",
                "price_from",
                "price_to",
                "currency",
                "status",
                "is_featured",
                "published_at",
            ]),
        );
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === "" ? null : $value;
    }

    private function slugExists(string $slug, ?string $ignoreId): bool
    {
        $query = Activity::query()->where("slug", $slug);

        if ($ignoreId !== null && $ignoreId !== "") {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
