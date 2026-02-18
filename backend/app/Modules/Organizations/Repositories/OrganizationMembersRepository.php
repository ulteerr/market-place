<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Organizations\Models\OrganizationMember;

final class OrganizationMembersRepository implements OrganizationMembersRepositoryInterface
{
    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = OrganizationMember::query()
            ->with([
                "user:id,first_name,last_name,middle_name,email",
                "invitedBy:id,first_name,last_name,middle_name,email",
            ])
            ->where("organization_id", $organizationId);

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";
            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("id", "like", $like)
                    ->orWhere("role_code", "like", $like)
                    ->orWhere("status", "like", $like)
                    ->orWhereHas("user", function (Builder $userBuilder) use ($like): void {
                        $userBuilder
                            ->where("email", "like", $like)
                            ->orWhere("first_name", "like", $like)
                            ->orWhere("last_name", "like", $like)
                            ->orWhere("middle_name", "like", $like);
                    });
            });
        }

        $status = trim((string) ($filters["status"] ?? ""));
        if ($status !== "") {
            $query->where("status", $status);
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowedSorts = ["created_at", "joined_at", "role_code", "status", "id"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function findByIdAndOrganization(string $id, string $organizationId): ?OrganizationMember
    {
        return OrganizationMember::query()
            ->where("id", $id)
            ->where("organization_id", $organizationId)
            ->first();
    }

    public function create(array $data): OrganizationMember
    {
        return OrganizationMember::query()->create($data);
    }

    public function update(OrganizationMember $member, array $data): OrganizationMember
    {
        $member->update($data);

        return $member;
    }

    public function delete(OrganizationMember $member): bool
    {
        return $member->delete();
    }
}
