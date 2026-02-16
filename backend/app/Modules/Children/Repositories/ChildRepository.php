<?php
declare(strict_types=1);

namespace Modules\Children\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Children\Models\Child;

final class ChildRepository implements ChildRepositoryInterface
{
    public function create(array $data): Child
    {
        return Child::create($data);
    }

    public function update(Child $child, array $data): Child
    {
        $child->update($data);
        return $child;
    }

    public function findById(string $id): ?Child
    {
        return Child::query()
            ->with(["user:id,first_name,last_name,middle_name,email"])
            ->find($id);
    }

    public function findByUserId(string $userId): Collection
    {
        return Child::query()->where("user_id", $userId)->get();
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Child::query()->select([
            "id",
            "user_id",
            "first_name",
            "last_name",
            "middle_name",
            "gender",
            "birth_date",
            "created_at",
        ]);

        $query->with(["user:id,first_name,last_name,middle_name,email"]);

        if (!empty($with)) {
            $query->with($with);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("first_name", "like", $like)
                    ->orWhere("last_name", "like", $like)
                    ->orWhere("middle_name", "like", $like)
                    ->orWhere("gender", "like", $like)
                    ->orWhere("user_id", "like", $like)
                    ->orWhereHas("user", function (Builder $userBuilder) use ($like): void {
                        $userBuilder
                            ->where("email", "like", $like)
                            ->orWhere("first_name", "like", $like)
                            ->orWhere("last_name", "like", $like)
                            ->orWhere("middle_name", "like", $like);
                    });
            });
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = [
            "first_name",
            "last_name",
            "middle_name",
            "gender",
            "birth_date",
            "user_id",
            "created_at",
        ];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy("created_at", "desc");
        }

        return $query->paginate($perPage);
    }

    public function delete(Child $child): bool
    {
        return $child->delete();
    }
}
