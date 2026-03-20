<?php

declare(strict_types=1);

namespace Modules\Categories\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Categories\Models\Category;

final class CategoriesRepository implements CategoriesRepositoryInterface
{
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    public function findById(string $id): ?Category
    {
        return Category::query()
            ->with(["parent", "children"])
            ->withCount(["children", "activities"])
            ->find($id);
    }

    public function findRoots(bool $onlyActive = false): Collection
    {
        return $this->baseTreeQuery($onlyActive)->whereNull("parent_id")->get();
    }

    public function findChildren(string $parentId, bool $onlyActive = false): Collection
    {
        return $this->baseTreeQuery($onlyActive)->where("parent_id", $parentId)->get();
    }

    public function getTree(bool $onlyActive = false): Collection
    {
        return $this->baseTreeQuery($onlyActive)
            ->whereNull("parent_id")
            ->with([
                "children" => function ($query) use ($onlyActive): void {
                    if ($onlyActive) {
                        $query->where("is_active", true);
                    }

                    $query->orderBy("sort_order")->orderBy("name");
                },
            ])
            ->get();
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Category::query()->select([
            "id",
            "name",
            "slug",
            "parent_id",
            "sort_order",
            "is_active",
            "created_at",
            "updated_at",
        ]);

        $query->with(["parent"]);
        $query->withCount(["children", "activities"]);

        if (!empty($with)) {
            $query->with($with);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("id", "like", $like)
                    ->orWhere("name", "like", $like)
                    ->orWhere("slug", "like", $like)
                    ->orWhereHas("parent", function (Builder $parentBuilder) use ($like): void {
                        $parentBuilder
                            ->where("name", "like", $like)
                            ->orWhere("slug", "like", $like);
                    });
            });
        }

        if (array_key_exists("is_active", $filters) && $filters["is_active"] !== null) {
            $query->where("is_active", (bool) $filters["is_active"]);
        }

        if (!empty($filters["parent_id"])) {
            if ($filters["parent_id"] === "root") {
                $query->whereNull("parent_id");
            } else {
                $query->where("parent_id", (string) $filters["parent_id"]);
            }
        }

        $sortBy = (string) ($filters["sort_by"] ?? "sort_order");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "asc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "asc";
        }

        $allowedSorts = ["name", "slug", "sort_order", "is_active", "created_at", "updated_at"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "sort_order";
        }

        $query->orderBy($sortBy, $sortDir)->orderBy("name");

        return $query->paginate($perPage);
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    private function baseTreeQuery(bool $onlyActive = false): Builder
    {
        $query = Category::query()
            ->select(["id", "name", "slug", "parent_id", "sort_order", "is_active"])
            ->orderBy("sort_order")
            ->orderBy("name");

        if ($onlyActive) {
            $query->where("is_active", true);
        }

        return $query;
    }
}
