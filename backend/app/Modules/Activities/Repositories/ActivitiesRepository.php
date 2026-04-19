<?php

declare(strict_types=1);

namespace Modules\Activities\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Activities\Models\Activity;

final class ActivitiesRepository implements ActivitiesRepositoryInterface
{
    public function create(array $data): Activity
    {
        return Activity::create($data);
    }

    public function update(Activity $activity, array $data): Activity
    {
        $activity->update($data);

        return $activity;
    }

    public function findById(string $id): ?Activity
    {
        return Activity::query()->with($this->defaultRelations())->find($id);
    }

    public function findByPublicRouteKey(string $publicKey): ?Activity
    {
        $uuid = $this->extractUuidFromPublicKey($publicKey);
        if ($uuid === null) {
            return null;
        }

        return Activity::query()->with($this->defaultRelations())->whereKey($uuid)->first();
    }

    public function featured(int $limit = 12, array $filters = []): Collection
    {
        $query = Activity::query()
            ->select($this->feedSelect())
            ->with($this->feedRelations())
            ->orderByDesc("published_at")
            ->orderByDesc("created_at");

        $this->applyFilters($query, [...$filters, "status" => "published", "is_featured" => true]);

        return $query->limit($limit)->get();
    }

    public function feed(int $limit = 20, ?string $cursor = null, array $filters = []): array
    {
        $limit = max(1, min(48, $limit));
        $sortBy = $this->normalizeFeedSortBy((string) ($filters["sort_by"] ?? "created_at"));
        $sortDir = $this->normalizeFeedSortDirection((string) ($filters["sort_dir"] ?? "desc"));

        $query = Activity::query()
            ->select($this->feedSelect())
            ->with($this->feedRelations())
            ->where("status", "published");

        $this->applyFilters($query, [
            ...$filters,
            "status" => "published",
            "sort_by" => $sortBy,
            "sort_dir" => $sortDir,
        ]);

        $cursorPayload = $this->decodeCursor($cursor);
        if ($cursorPayload !== null) {
            $query->where(function (Builder $builder) use ($cursorPayload): void {
                if (($cursorPayload["sort_dir"] ?? "desc") === "asc") {
                    $builder
                        ->where("created_at", ">", $cursorPayload["created_at"])
                        ->orWhere(function (Builder $nested) use ($cursorPayload): void {
                            $nested
                                ->where("created_at", "=", $cursorPayload["created_at"])
                                ->where("id", ">", $cursorPayload["id"]);
                        });

                    return;
                }

                $builder
                    ->where("created_at", "<", $cursorPayload["created_at"])
                    ->orWhere(function (Builder $nested) use ($cursorPayload): void {
                        $nested
                            ->where("created_at", "=", $cursorPayload["created_at"])
                            ->where("id", "<", $cursorPayload["id"]);
                    });
            });
        }

        if ($sortDir === "asc") {
            $query->orderBy("created_at")->orderBy("id");
        } else {
            $query->orderByDesc("created_at")->orderByDesc("id");
        }

        $items = $query->limit($limit + 1)->get();

        $nextCursor = null;
        if ($items->count() > $limit) {
            $cursorItem = $items->pop();
            if ($cursorItem instanceof Activity) {
                $nextCursor = $this->encodeCursor($cursorItem, $sortDir);
            }
        }

        return [
            "items" => $items->values(),
            "next_cursor" => $nextCursor,
        ];
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Activity::query()->select($this->defaultSelect());

        $query->with(array_merge($this->defaultRelations(), $with));
        $this->applyFilters($query, $filters);

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = [
            "name",
            "status",
            "is_featured",
            "published_at",
            "price_from",
            "price_to",
            "created_at",
            "updated_at",
        ];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir)->orderBy("name");

        return $query->paginate($perPage);
    }

    public function delete(Activity $activity): bool
    {
        return $activity->delete();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("id", "like", $like)
                    ->orWhere("name", "like", $like)
                    ->orWhere("slug", "like", $like)
                    ->orWhere("short_description", "like", $like)
                    ->orWhere("description", "like", $like)
                    ->orWhereHas("organization", function (Builder $organizationBuilder) use (
                        $like,
                    ): void {
                        $organizationBuilder->where("name", "like", $like);
                    })
                    ->orWhereHas("location", function (Builder $locationBuilder) use ($like): void {
                        $locationBuilder->where("address", "like", $like);
                    })
                    ->orWhereHas("primaryCategory", function (Builder $categoryBuilder) use (
                        $like,
                    ): void {
                        $categoryBuilder
                            ->where("name", "like", $like)
                            ->orWhere("slug", "like", $like);
                    });
            });
        }

        if (!empty($filters["status"])) {
            $query->where("status", (string) $filters["status"]);
        }

        if (
            array_key_exists("is_featured", $filters) &&
            $filters["is_featured"] !== null &&
            $filters["is_featured"] !== ""
        ) {
            $query->where("is_featured", filter_var($filters["is_featured"], FILTER_VALIDATE_BOOL));
        }

        if (!empty($filters["organization_id"])) {
            $query->where("organization_id", (string) $filters["organization_id"]);
        }

        if (!empty($filters["location_id"])) {
            $query->where("location_id", (string) $filters["location_id"]);
        }

        if (!empty($filters["category_id"])) {
            $categoryId = (string) $filters["category_id"];
            $query->whereHas("primaryCategory", function (Builder $categoryBuilder) use (
                $categoryId,
            ): void {
                $categoryBuilder->where("categories.id", $categoryId);
            });
        }

        if (!empty($filters["root_category_id"])) {
            $rootCategoryId = (string) $filters["root_category_id"];
            $query->whereHas("primaryCategory", function (Builder $categoryBuilder) use (
                $rootCategoryId,
            ): void {
                $categoryBuilder->where("categories.parent_id", $rootCategoryId);
            });
        }

        if (!empty($filters["city_id"])) {
            $cityId = (string) $filters["city_id"];
            $query->whereHas("location", function (Builder $locationBuilder) use ($cityId): void {
                $locationBuilder->where("city_id", $cityId);
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function defaultRelations(): array
    {
        return [
            "organization:id,name",
            "location:id,organization_id,city_id,address",
            "location.city:id,name",
            "primaryCategory:id,name,slug,parent_id,sort_order,is_active",
            "primaryCategory.parent:id,name,slug,parent_id,sort_order,is_active",
            "schedules",
            "cover:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
            "gallery:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
        ];
    }

    /**
     * @return array<int, string>
     */
    private function feedRelations(): array
    {
        return [
            "organization:id,name",
            "location:id,city_id,address",
            "location.city:id,name",
            "primaryCategory:id,name,slug,parent_id",
            "primaryCategory.parent:id,name,slug",
            "cover:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
        ];
    }

    /**
     * @return array<int, string>
     */
    private function defaultSelect(): array
    {
        return [
            "id",
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
            "created_at",
            "updated_at",
        ];
    }

    /**
     * @return array<int, string>
     */
    private function feedSelect(): array
    {
        return [
            "id",
            "organization_id",
            "location_id",
            "name",
            "slug",
            "short_description",
            "min_age",
            "max_age",
            "price_from",
            "price_to",
            "currency",
            "is_featured",
            "published_at",
            "created_at",
        ];
    }

    private function extractUuidFromPublicKey(string $publicKey): ?string
    {
        $value = trim($publicKey);
        if ($value === "") {
            return null;
        }

        if (
            preg_match(
                '/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12})$/',
                $value,
                $matches,
            ) !== 1
        ) {
            return null;
        }

        return strtolower($matches[1]);
    }

    /**
     * @return array{created_at: string, id: string, sort_dir?: string}|null
     */
    private function decodeCursor(?string $cursor): ?array
    {
        if (!is_string($cursor) || trim($cursor) === "") {
            return null;
        }

        $decoded = base64_decode($cursor, true);
        if (!is_string($decoded) || $decoded === "") {
            return null;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return null;
        }

        $createdAt = isset($payload["created_at"]) ? trim((string) $payload["created_at"]) : "";
        $id = isset($payload["id"]) ? trim((string) $payload["id"]) : "";
        if ($createdAt === "" || $id === "") {
            return null;
        }

        return [
            "created_at" => $createdAt,
            "id" => $id,
            "sort_dir" => $this->normalizeFeedSortDirection(
                (string) ($payload["sort_dir"] ?? "desc"),
            ),
        ];
    }

    private function encodeCursor(Activity $activity, string $sortDir = "desc"): string
    {
        return base64_encode(
            (string) json_encode(
                [
                    "created_at" =>
                        optional($activity->created_at)?->toISOString() ??
                        (string) $activity->created_at,
                    "id" => (string) $activity->id,
                    "sort_dir" => $this->normalizeFeedSortDirection($sortDir),
                ],
                JSON_UNESCAPED_SLASHES,
            ),
        );
    }

    private function normalizeFeedSortBy(string $sortBy): string
    {
        return $sortBy === "created_at" ? "created_at" : "created_at";
    }

    private function normalizeFeedSortDirection(string $sortDir): string
    {
        return strtolower($sortDir) === "asc" ? "asc" : "desc";
    }
}
