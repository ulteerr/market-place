<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Illuminate\Support\Arr;
use Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class UsersRepository implements UsersRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create(
            Arr::except($data, ["roles", "avatar", "avatar_delete", "permission_overrides"]),
        );
    }

    public function update(User $user, array $data): User
    {
        $user->update(
            Arr::except($data, ["roles", "avatar", "avatar_delete", "permission_overrides"]),
        );
        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where("email", $email)->first();
    }

    public function findByEmailOrPhone(string $value): ?User
    {
        return User::where("email", $value)->orWhere("phone", $value)->first();
    }

    public function findById(string $id): ?User
    {
        return User::query()
            ->with([
                "roles:id,code",
                "avatar:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
                "permissionOverrides.permission:id,code",
            ])
            ->find($id);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        $query = User::query()
            ->select([
                "id",
                "email",
                "first_name",
                "last_name",
                "middle_name",
                "gender",
                "phone",
                "created_at",
                "updated_at",
            ])
            ->with([
                "roles:id,code",
                "avatar:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
            ]);

        if (!empty($with)) {
            $query->with($with);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $nameTerms = preg_split("/\s+/u", $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $query->where(function (Builder $subQuery) use ($like, $nameTerms) {
                $subQuery
                    ->where("id", "like", $like)
                    ->orWhere("email", "like", $like)
                    ->orWhere("phone", "like", $like)
                    ->orWhereHas("roles", function (Builder $roleQuery) use ($like) {
                        $roleQuery->where("code", "like", $like)->orWhere("label", "like", $like);
                    });

                if (!empty($nameTerms)) {
                    $subQuery->orWhere(function (Builder $nameQuery) use ($nameTerms): void {
                        foreach ($nameTerms as $term) {
                            $termLike = "%" . $term . "%";

                            $nameQuery->where(function (Builder $singleTermQuery) use (
                                $termLike,
                            ): void {
                                $singleTermQuery
                                    ->where("first_name", "like", $termLike)
                                    ->orWhere("last_name", "like", $termLike)
                                    ->orWhere("middle_name", "like", $termLike)
                                    ->orWhere("gender", "like", $termLike);
                            });
                        }
                    });
                }
            });
        }

        $accessGroup = trim((string) ($filters["access_group"] ?? ""));
        if ($accessGroup === "admin") {
            $query->whereHas(
                "roles",
                fn(Builder $roleQuery) => $roleQuery->where("code", "!=", "participant"),
            );
        } elseif ($accessGroup === "basic") {
            $query->whereDoesntHave(
                "roles",
                fn(Builder $roleQuery) => $roleQuery->where("code", "!=", "participant"),
            );
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = ["id", "first_name", "last_name", "middle_name", "gender", "created_at"];

        if ($sortBy === "name") {
            $query
                ->orderBy("last_name", $sortDir)
                ->orderBy("first_name", $sortDir)
                ->orderBy("middle_name", $sortDir);
        } elseif ($sortBy === "access") {
            $query
                ->withCount([
                    "roles as admin_roles_count" => fn(Builder $roleQuery) => $roleQuery->where(
                        "code",
                        "!=",
                        "participant",
                    ),
                ])
                ->orderBy("admin_roles_count", $sortDir)
                ->orderBy("last_name", "asc")
                ->orderBy("first_name", "asc");
        } elseif (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy("created_at", "desc");
        }

        return $query->paginate($perPage);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
