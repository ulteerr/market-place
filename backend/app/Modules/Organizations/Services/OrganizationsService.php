<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Users\Models\User;
use RuntimeException;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;

final class OrganizationsService
{
    public function __construct(private readonly OrganizationsRepositoryInterface $repository) {}

    public function createOrganization(array $data): Organization
    {
        return $this->repository->create($data);
    }

    public function create(array $data): Organization
    {
        return $this->createOrganization($data);
    }

    public function updateOrganization(Organization $organization, array $data): Organization
    {
        return $this->repository->update($organization, $data);
    }

    public function update(string $id, array $data): Organization
    {
        $organization = $this->getOrganizationById($id);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        return $this->updateOrganization($organization, $data);
    }

    public function getOrganizationById(string $id): ?Organization
    {
        return $this->repository->findById($id);
    }

    public function findById(string $id): ?Organization
    {
        return $this->getOrganizationById($id);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $with, $filters);
    }

    public function myOrganizations(
        User $actor,
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginateForUser((string) $actor->id, $perPage, $with, $filters);
    }

    public function transferOwnership(
        string $organizationId,
        User $actor,
        string $targetUserId,
    ): Organization {
        $organization = $this->getOrganizationById($organizationId);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        if ((string) $organization->owner_user_id !== (string) $actor->id) {
            throw new AuthorizationException("Forbidden");
        }

        if ((string) $actor->id === $targetUserId) {
            throw ValidationException::withMessages([
                "target_user_id" => "Target user is already the owner.",
            ]);
        }

        $ownerRoleId = $this->resolveRoleId("owner");
        $adminRoleId = $this->resolveRoleId("admin");

        DB::transaction(function () use (
            $organization,
            $actor,
            $targetUserId,
            $ownerRoleId,
            $adminRoleId,
        ): void {
            OrganizationMember::query()->updateOrCreate(
                [
                    "organization_id" => (string) $organization->id,
                    "user_id" => (string) $targetUserId,
                ],
                [
                    "role_id" => $ownerRoleId,
                    "role_code" => "owner",
                    "status" => "active",
                    "joined_at" => now(),
                ],
            );

            OrganizationMember::query()->updateOrCreate(
                [
                    "organization_id" => (string) $organization->id,
                    "user_id" => (string) $actor->id,
                ],
                [
                    "role_id" => $adminRoleId,
                    "role_code" => "admin",
                    "status" => "active",
                    "joined_at" => now(),
                ],
            );

            $this->repository->update($organization, [
                "owner_user_id" => $targetUserId,
                "ownership_status" => "claimed",
                "claimed_at" => now(),
            ]);
        });

        return $this->getOrganizationById($organizationId) ?? $organization;
    }

    public function delete(Organization $organization): bool
    {
        return $this->repository->delete($organization);
    }

    public function deleteById(string $id): void
    {
        $organization = $this->getOrganizationById($id);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        $this->delete($organization);
    }

    private function resolveRoleId(string $roleCode): string
    {
        $role = OrganizationRole::query()->firstOrCreate(["code" => $roleCode]);

        return (string) $role->id;
    }
}
