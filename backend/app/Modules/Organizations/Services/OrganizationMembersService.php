<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Organizations\Repositories\OrganizationMembersRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Users\Models\User;
use RuntimeException;

final class OrganizationMembersService
{
    public function __construct(
        private readonly OrganizationMembersRepositoryInterface $repository,
        private readonly OrganizationsRepositoryInterface $organizationsRepository,
    ) {}

    public function listForOrganization(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);
        $this->assertCanManageMembers($organization, $actor);

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
        string $roleCode = "member",
        string $status = "active",
    ): OrganizationMember {
        $organization = $this->findOrganizationOrFail($organizationId);
        $this->assertCanManageMembers($organization, $actor);
        $this->assertActorCanAssignRole($organization, $actor, $roleCode);

        $existing = OrganizationMember::query()
            ->where("organization_id", (string) $organization->id)
            ->where("user_id", $userId)
            ->first();

        $roleId = $this->resolveRoleId($roleCode);

        if ($existing) {
            if (
                $this->isOwnerMember($existing) &&
                !$this->isOrganizationOwner($organization, $actor)
            ) {
                throw new AuthorizationException("Forbidden");
            }

            return $this->repository->update($existing, [
                "role_id" => $roleId,
                "role_code" => $roleCode,
                "status" => $status,
                "invited_by_user_id" => (string) $actor->id,
                "joined_at" => $status === "active" ? now() : $existing->joined_at,
            ]);
        }

        return $this->repository->create([
            "organization_id" => (string) $organization->id,
            "user_id" => $userId,
            "role_id" => $roleId,
            "role_code" => $roleCode,
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
    ): OrganizationMember {
        $organization = $this->findOrganizationOrFail($organizationId);
        $this->assertCanManageMembers($organization, $actor);

        $member = $this->findMemberOrFail((string) $organization->id, $memberId);
        if ($this->isOwnerMember($member) && !$this->isOrganizationOwner($organization, $actor)) {
            throw new AuthorizationException("Forbidden");
        }

        $nextRoleCode = (string) ($data["role_code"] ?? ($member->role_code ?? "member"));
        $this->assertActorCanAssignRole($organization, $actor, $nextRoleCode);

        $updateData = [];
        if (array_key_exists("role_code", $data)) {
            $updateData["role_code"] = $nextRoleCode;
            $updateData["role_id"] = $this->resolveRoleId($nextRoleCode);
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
        $this->assertCanManageMembers($organization, $actor);

        $member = $this->findMemberOrFail((string) $organization->id, $memberId);
        if ($this->isOwnerMember($member) && !$this->isOrganizationOwner($organization, $actor)) {
            throw new AuthorizationException("Forbidden");
        }

        if ($this->isOwnerMember($member) && (string) $member->user_id === (string) $actor->id) {
            throw ValidationException::withMessages([
                "member_id" => "Organization owner cannot remove himself.",
            ]);
        }

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

    private function findMemberOrFail(string $organizationId, string $memberId): OrganizationMember
    {
        $member = $this->repository->findByIdAndOrganization($memberId, $organizationId);
        if (!$member) {
            throw new RuntimeException("Organization member not found");
        }

        return $member;
    }

    private function resolveRoleId(string $roleCode): string
    {
        $normalizedRoleCode = trim($roleCode) !== "" ? trim($roleCode) : "member";
        $role = OrganizationRole::query()->firstOrCreate(["code" => $normalizedRoleCode]);

        return (string) $role->id;
    }

    private function assertCanManageMembers(Organization $organization, User $actor): void
    {
        if ($this->isOrganizationOwner($organization, $actor)) {
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

    private function assertActorCanAssignRole(
        Organization $organization,
        User $actor,
        string $roleCode,
    ): void {
        if ($roleCode !== "owner") {
            return;
        }

        throw ValidationException::withMessages([
            "role_code" => "Owner role can only be changed via ownership transfer endpoint.",
        ]);
    }

    private function isOrganizationOwner(Organization $organization, User $actor): bool
    {
        return (string) $organization->owner_user_id === (string) $actor->id;
    }

    private function isOwnerMember(OrganizationMember $member): bool
    {
        return (string) $member->role_code === "owner";
    }
}
