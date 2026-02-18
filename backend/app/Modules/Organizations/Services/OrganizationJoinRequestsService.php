<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Organizations\Repositories\OrganizationJoinRequestsRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Users\Models\User;
use RuntimeException;

final class OrganizationJoinRequestsService
{
    public function __construct(
        private readonly OrganizationJoinRequestsRepositoryInterface $repository,
        private readonly OrganizationsRepositoryInterface $organizationsRepository,
    ) {}

    public function submit(
        string $organizationId,
        User $actor,
        ?string $message = null,
    ): OrganizationJoinRequest {
        $organization = $this->findOrganizationOrFail($organizationId);

        $isActiveMember = OrganizationMember::query()
            ->where("organization_id", (string) $organization->id)
            ->where("user_id", (string) $actor->id)
            ->where("status", "active")
            ->exists();

        if ($isActiveMember) {
            throw ValidationException::withMessages([
                "organization_id" => "User is already an active member of this organization.",
            ]);
        }

        $pendingRequest = $this->repository->findPendingByOrganizationAndUser(
            (string) $organization->id,
            (string) $actor->id,
        );

        if ($pendingRequest) {
            throw ValidationException::withMessages([
                "organization_id" => "Pending join request already exists.",
            ]);
        }

        return $this->repository->create([
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $actor->id,
            "status" => "pending",
            "message" => $message,
        ]);
    }

    public function listForOrganization(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);
        $this->assertCanManageJoinRequests($organization, $actor);

        return $this->repository->paginateForOrganization(
            (string) $organization->id,
            $perPage,
            $filters,
        );
    }

    public function myRequests(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);

        return $this->repository->paginateForOrganizationAndUser(
            (string) $organization->id,
            (string) $actor->id,
            $perPage,
            $filters,
        );
    }

    public function approve(
        string $organizationId,
        string $joinRequestId,
        User $actor,
        string $roleCode = "member",
        ?string $reviewNote = null,
    ): OrganizationJoinRequest {
        $organization = $this->findOrganizationOrFail($organizationId);
        $this->assertCanManageJoinRequests($organization, $actor);

        $request = $this->findJoinRequestOrFail((string) $organization->id, $joinRequestId);
        $this->assertPending($request);

        $roleId = $this->resolveRoleId($roleCode);

        DB::transaction(function () use ($request, $actor, $roleCode, $roleId, $reviewNote): void {
            $this->repository->update($request, [
                "status" => "approved",
                "review_note" => $reviewNote,
                "reviewed_by_user_id" => (string) $actor->id,
                "reviewed_at" => now(),
            ]);

            OrganizationMember::query()->updateOrCreate(
                [
                    "organization_id" => (string) $request->organization_id,
                    "user_id" => (string) $request->user_id,
                ],
                [
                    "role_id" => $roleId,
                    "role_code" => $roleCode,
                    "status" => "active",
                    "invited_by_user_id" => (string) $actor->id,
                    "joined_at" => now(),
                ],
            );
        });

        return $request->fresh(["user", "reviewedBy"]) ?? $request;
    }

    public function reject(
        string $organizationId,
        string $joinRequestId,
        User $actor,
        ?string $reviewNote = null,
    ): OrganizationJoinRequest {
        $organization = $this->findOrganizationOrFail($organizationId);
        $this->assertCanManageJoinRequests($organization, $actor);

        $request = $this->findJoinRequestOrFail((string) $organization->id, $joinRequestId);
        $this->assertPending($request);

        $this->repository->update($request, [
            "status" => "rejected",
            "review_note" => $reviewNote,
            "reviewed_by_user_id" => (string) $actor->id,
            "reviewed_at" => now(),
        ]);

        return $request->fresh(["user", "reviewedBy"]) ?? $request;
    }

    private function findOrganizationOrFail(string $organizationId): Organization
    {
        $organization = $this->organizationsRepository->findById($organizationId);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        return $organization;
    }

    private function findJoinRequestOrFail(
        string $organizationId,
        string $joinRequestId,
    ): OrganizationJoinRequest {
        $request = $this->repository->findByIdAndOrganization($joinRequestId, $organizationId);
        if (!$request) {
            throw new RuntimeException("Join request not found");
        }

        return $request;
    }

    private function assertPending(OrganizationJoinRequest $request): void
    {
        if ((string) $request->status !== "pending") {
            throw ValidationException::withMessages([
                "status" => "Only pending join request can be reviewed.",
            ]);
        }
    }

    private function assertCanManageJoinRequests(Organization $organization, User $actor): void
    {
        if ((string) $organization->owner_user_id === (string) $actor->id) {
            return;
        }

        $isOrgAdmin = OrganizationMember::query()
            ->where("organization_id", (string) $organization->id)
            ->where("user_id", (string) $actor->id)
            ->where("status", "active")
            ->whereIn("role_code", ["owner", "admin"])
            ->exists();

        if ($isOrgAdmin) {
            return;
        }

        throw new AuthorizationException("Forbidden");
    }

    private function resolveRoleId(string $roleCode): string
    {
        $normalizedRoleCode = trim($roleCode) !== "" ? trim($roleCode) : "member";

        $role = OrganizationRole::query()->firstOrCreate([
            "code" => $normalizedRoleCode,
        ]);

        return (string) $role->id;
    }
}
