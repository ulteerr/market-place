<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organizations\Models\OrganizationLocation;
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
        return DB::transaction(function () use ($data): Organization {
            $organization = $this->repository->create(Arr::except($data, ["locations"]));

            if (array_key_exists("locations", $data)) {
                $this->syncLocations($organization, (array) $data["locations"]);
            }

            return $organization->fresh([
                "owner:id,first_name,last_name,middle_name,email",
                "locations",
            ]) ?? $organization;
        });
    }

    public function create(array $data): Organization
    {
        return $this->createOrganization($data);
    }

    public function updateOrganization(Organization $organization, array $data): Organization
    {
        return DB::transaction(function () use ($organization, $data): Organization {
            $organization = $this->repository->update(
                $organization,
                Arr::except($data, ["locations"]),
            );

            if (array_key_exists("locations", $data)) {
                $this->syncLocations($organization, (array) $data["locations"]);
            }

            return $organization->fresh([
                "owner:id,first_name,last_name,middle_name,email",
                "locations",
            ]) ?? $organization;
        });
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

    /**
     * @param array<int, array<string, mixed>> $locations
     */
    private function syncLocations(Organization $organization, array $locations): void
    {
        OrganizationLocation::query()
            ->where("organization_id", (string) $organization->id)
            ->delete();

        foreach ($locations as $location) {
            OrganizationLocation::query()->create([
                "organization_id" => (string) $organization->id,
                "country_id" => $location["country_id"] ?? null,
                "region_id" => $location["region_id"] ?? null,
                "city_id" => $location["city_id"] ?? null,
                "district_id" => $location["district_id"] ?? null,
                "address" => $location["address"] ?? null,
                "lat" => $location["lat"] ?? null,
                "lng" => $location["lng"] ?? null,
            ]);
        }
    }
}
