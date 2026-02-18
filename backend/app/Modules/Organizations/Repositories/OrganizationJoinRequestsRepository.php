<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Organizations\Models\OrganizationJoinRequest;

final class OrganizationJoinRequestsRepository implements
    OrganizationJoinRequestsRepositoryInterface
{
    public function create(array $data): OrganizationJoinRequest
    {
        return OrganizationJoinRequest::query()->create($data);
    }

    public function findByIdAndOrganization(
        string $id,
        string $organizationId,
    ): ?OrganizationJoinRequest {
        return OrganizationJoinRequest::query()
            ->where("id", $id)
            ->where("organization_id", $organizationId)
            ->first();
    }

    public function findPendingByOrganizationAndUser(
        string $organizationId,
        string $userId,
    ): ?OrganizationJoinRequest {
        return OrganizationJoinRequest::query()
            ->where("organization_id", $organizationId)
            ->where("user_id", $userId)
            ->where("status", "pending")
            ->first();
    }

    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = OrganizationJoinRequest::query()
            ->with([
                "user:id,first_name,last_name,middle_name,email",
                "reviewedBy:id,first_name,last_name,middle_name,email",
            ])
            ->where("organization_id", $organizationId);

        $status = trim((string) ($filters["status"] ?? ""));
        if ($status !== "") {
            $query->where("status", $status);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("id", "like", $like)
                    ->orWhere("message", "like", $like)
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

        $allowedSorts = ["created_at", "reviewed_at", "status", "id"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function paginateForOrganizationAndUser(
        string $organizationId,
        string $userId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = OrganizationJoinRequest::query()
            ->with(["reviewedBy:id,first_name,last_name,middle_name,email"])
            ->where("organization_id", $organizationId)
            ->where("user_id", $userId);

        $status = trim((string) ($filters["status"] ?? ""));
        if ($status !== "") {
            $query->where("status", $status);
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = ["created_at", "reviewed_at", "status", "id"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function update(OrganizationJoinRequest $request, array $data): OrganizationJoinRequest
    {
        $request->update($data);

        return $request;
    }
}
