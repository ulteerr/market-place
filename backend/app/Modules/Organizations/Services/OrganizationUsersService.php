<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationUser;
use Modules\Organizations\Repositories\OrganizationUsersRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Users\Models\User;
use RuntimeException;

final class OrganizationUsersService
{
    public function __construct(
        private readonly OrganizationUsersRepositoryInterface $repository,
        private readonly OrganizationsRepositoryInterface $organizationsRepository,
    ) {}

    public function listForOrganization(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("viewMembers", $organization);

        return $this->repository->paginateForOrganization(
            (string) $organization->id,
            $perPage,
            $filters,
        );
    }

    public function addMember(
        string $organizationId,
        User $actor,
        string $userId,
        ?string $position = null,
        string $status = "active",
    ): OrganizationUser {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("manageMembers", $organization);

        $existing = OrganizationUser::query()
            ->where("organization_id", (string) $organization->id)
            ->where("user_id", $userId)
            ->first();

        if ($existing) {
            return $this->repository->update($existing, [
                "position" => $position,
                "status" => $status,
                "invited_by_user_id" => (string) $actor->id,
                "joined_at" => $status === "active" ? now() : $existing->joined_at,
            ]);
        }

        return $this->repository->create([
            "organization_id" => (string) $organization->id,
            "user_id" => $userId,
            "position" => $position,
            "status" => $status,
            "invited_by_user_id" => (string) $actor->id,
            "joined_at" => $status === "active" ? now() : null,
        ]);
    }

    public function updateMember(
        string $organizationId,
        string $memberId,
        User $actor,
        array $data,
    ): OrganizationUser {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("manageMembers", $organization);

        $member = $this->findMemberOrFail((string) $organization->id, $memberId);

        $updateData = [];
        if (array_key_exists("position", $data)) {
            $updateData["position"] = $data["position"];
        }
        if (array_key_exists("status", $data)) {
            $updateData["status"] = (string) $data["status"];
        }

        if (($updateData["status"] ?? $member->status) === "active" && !$member->joined_at) {
            $updateData["joined_at"] = now();
        }

        return $this->repository->update($member, $updateData);
    }

    public function removeMember(string $organizationId, string $memberId, User $actor): void
    {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("manageMembers", $organization);

        $member = $this->findMemberOrFail((string) $organization->id, $memberId);
        $this->repository->delete($member);
    }

    private function findOrganizationOrFail(string $organizationId): Organization
    {
        $organization = $this->organizationsRepository->findById($organizationId);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        return $organization;
    }

    private function findMemberOrFail(string $organizationId, string $memberId): OrganizationUser
    {
        $member = $this->repository->findByIdAndOrganization($memberId, $organizationId);
        if (!$member) {
            throw new RuntimeException("Organization member not found");
        }

        return $member;
    }
}
