<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationClient;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Users\Models\User;
use RuntimeException;

final class OrganizationClientsService
{
    public function __construct(
        private readonly OrganizationsRepositoryInterface $organizationsRepository,
    ) {}

    public function listForOrganization(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("viewClients", $organization);

        $query = OrganizationClient::query()
            ->with([
                "subjectUser:id,first_name,last_name,middle_name,email",
                "subjectChild:id,first_name,last_name,middle_name,user_id",
                "addedBy:id,first_name,last_name,middle_name,email",
            ])
            ->where("organization_id", (string) $organization->id);

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
                    ->orWhere("subject_id", "like", $like)
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

        $allowedSorts = ["created_at", "joined_at", "status", "id", "subject_type"];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    private function findOrganizationOrFail(string $organizationId): Organization
    {
        $organization = $this->organizationsRepository->findById($organizationId);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        return $organization;
    }
}
