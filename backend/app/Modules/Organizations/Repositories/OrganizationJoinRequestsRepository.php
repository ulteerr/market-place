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

    public function findPendingByOrganizationAndSubject(
        string $organizationId,
        string $subjectType,
        string $subjectId,
    ): ?OrganizationJoinRequest {
        return OrganizationJoinRequest::query()
            ->where("organization_id", $organizationId)
            ->where("subject_type", $subjectType)
            ->where("subject_id", $subjectId)
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
                "requestedBy:id,first_name,last_name,middle_name,email",
                "subjectUser:id,first_name,last_name,middle_name,email",
                "subjectChild:id,first_name,last_name,middle_name,user_id",
                "reviewedBy:id,first_name,last_name,middle_name,email",
            ])
            ->where("organization_id", $organizationId);

        $status = trim((string) ($filters["status"] ?? ""));
        if ($status !== "") {
            $query->where("status", $status);
        }

        $subjectType = trim((string) ($filters["subject_type"] ?? ""));
        if ($subjectType !== "") {
            $query->where("subject_type", $subjectType);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("id", "like", $like)
                    ->orWhere("message", "like", $like)
                    ->orWhereHas("requestedBy", function (Builder $userBuilder) use ($like): void {
                        $userBuilder
                            ->where("email", "like", $like)
                            ->orWhere("first_name", "like", $like)
                            ->orWhere("last_name", "like", $like)
                            ->orWhere("middle_name", "like", $like);
                    })
                    ->orWhereHas("subjectUser", function (Builder $userBuilder) use ($like): void {
                        $userBuilder
                            ->where("email", "like", $like)
                            ->orWhere("first_name", "like", $like)
                            ->orWhere("last_name", "like", $like)
                            ->orWhere("middle_name", "like", $like);
                    })
                    ->orWhereHas("subjectChild", function (Builder $childBuilder) use (
                        $like,
                    ): void {
                        $childBuilder
                            ->where("first_name", "like", $like)
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

        $allowedSorts = ["created_at", "reviewed_at", "status", "id", "subject_type"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function paginateForOrganizationAndRequester(
        string $organizationId,
        string $requestedByUserId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = OrganizationJoinRequest::query()
            ->with([
                "requestedBy:id,first_name,last_name,middle_name,email",
                "subjectUser:id,first_name,last_name,middle_name,email",
                "subjectChild:id,first_name,last_name,middle_name,user_id",
                "reviewedBy:id,first_name,last_name,middle_name,email",
            ])
            ->where("organization_id", $organizationId)
            ->where("requested_by_user_id", $requestedByUserId);

        $status = trim((string) ($filters["status"] ?? ""));
        if ($status !== "") {
            $query->where("status", $status);
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = ["created_at", "reviewed_at", "status", "id", "subject_type"];
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
