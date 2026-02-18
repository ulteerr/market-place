<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Organizations\Models\Organization;

final class OrganizationsRepository implements OrganizationsRepositoryInterface
{
    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization;
    }

    public function findById(string $id): ?Organization
    {
        return Organization::query()
            ->with(["owner:id,first_name,last_name,middle_name,email"])
            ->find($id);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Organization::query()->select([
            "id",
            "name",
            "description",
            "address",
            "phone",
            "email",
            "status",
            "source_type",
            "ownership_status",
            "owner_user_id",
            "created_by_user_id",
            "claimed_at",
            "created_at",
            "updated_at",
        ]);

        $query->with(["owner:id,first_name,last_name,middle_name,email"]);

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
                    ->orWhere("description", "like", $like)
                    ->orWhere("email", "like", $like)
                    ->orWhere("phone", "like", $like)
                    ->orWhere("address", "like", $like)
                    ->orWhere("status", "like", $like)
                    ->orWhere("ownership_status", "like", $like)
                    ->orWhere("source_type", "like", $like);
            });
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = [
            "id",
            "name",
            "email",
            "status",
            "source_type",
            "ownership_status",
            "claimed_at",
            "created_at",
        ];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy("created_at", "desc");
        }

        return $query->paginate($perPage);
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }

    public function paginateForUser(
        string $userId,
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Organization::query()
            ->select([
                "id",
                "name",
                "description",
                "address",
                "phone",
                "email",
                "status",
                "source_type",
                "ownership_status",
                "owner_user_id",
                "created_by_user_id",
                "claimed_at",
                "created_at",
                "updated_at",
            ])
            ->with(["owner:id,first_name,last_name,middle_name,email"]);

        if (!empty($with)) {
            $query->with($with);
        }

        $query->where(function (Builder $builder) use ($userId): void {
            $builder
                ->where("owner_user_id", $userId)
                ->orWhereHas("members", function (Builder $memberBuilder) use ($userId): void {
                    $memberBuilder->where("user_id", $userId)->where("status", "active");
                });
        });

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("id", "like", $like)
                    ->orWhere("name", "like", $like)
                    ->orWhere("description", "like", $like)
                    ->orWhere("email", "like", $like)
                    ->orWhere("phone", "like", $like)
                    ->orWhere("address", "like", $like);
            });
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = ["id", "name", "email", "status", "claimed_at", "created_at"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }
}
