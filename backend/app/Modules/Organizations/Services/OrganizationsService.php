<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use App\Shared\Services\RelatedStateLogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organizations\DTOs\OrganizationLocationData;
use Modules\Organizations\DTOs\OrganizationMetroConnectionData;
use Modules\Organizations\DTOs\OrganizationUpsertData;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Organizations\Models\OrganizationLocationMetroStation;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Users\Models\User;
use RuntimeException;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;

final class OrganizationsService
{
    public function __construct(
        private readonly OrganizationsRepositoryInterface $repository,
        private readonly RelatedStateLogService $relatedStateLogService,
    ) {}

    public function createOrganization(array $data): Organization
    {
        return DB::transaction(function () use ($data): Organization {
            $upsertData = OrganizationUpsertData::fromArray($data);
            $organization = $this->repository->create($upsertData->organizationAttributes);

            if ($upsertData->hasLocations) {
                $this->syncLocations($organization, $upsertData->locations);
                $relatedSnapshot = $this->buildRelatedSnapshot($organization);
                $this->relatedStateLogService->enrichCreateChangeLogWithRelatedState(
                    $organization,
                    $relatedSnapshot,
                );
                $this->relatedStateLogService->enrichCreateActionLogWithRelatedState(
                    $organization,
                    $relatedSnapshot,
                );
            }

            return $organization->fresh([
                "owner:id,first_name,last_name,middle_name,email",
                "locations.metroConnections.metroStation.metroLine",
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
            $upsertData = OrganizationUpsertData::fromArray($data);
            $beforeRelatedSnapshot = $upsertData->hasLocations
                ? $this->buildRelatedSnapshot($organization)
                : [];

            $organization = $this->repository->update(
                $organization,
                $upsertData->organizationAttributes,
            );

            if ($upsertData->hasLocations) {
                $this->syncLocations($organization, $upsertData->locations);
                $afterRelatedSnapshot = $this->buildRelatedSnapshot($organization);
                $this->relatedStateLogService->writeRelatedChangeLogIfChanged(
                    $organization,
                    $beforeRelatedSnapshot,
                    $afterRelatedSnapshot,
                );
                $this->relatedStateLogService->writeRelatedActionLogIfChanged(
                    $organization,
                    $beforeRelatedSnapshot,
                    $afterRelatedSnapshot,
                );
            }

            return $organization->fresh([
                "owner:id,first_name,last_name,middle_name,email",
                "locations.metroConnections.metroStation.metroLine",
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
     * @param array<int, OrganizationLocationData> $locations
     */
    private function syncLocations(Organization $organization, array $locations): void
    {
        OrganizationLocation::query()
            ->where("organization_id", (string) $organization->id)
            ->delete();

        foreach ($locations as $location) {
            $createdLocation = OrganizationLocation::query()->create([
                "organization_id" => (string) $organization->id,
                "country_id" => $location->countryId,
                "region_id" => $location->regionId,
                "city_id" => $location->cityId,
                "district_id" => $location->districtId,
                "address" => $location->address,
                "lat" => $location->lat,
                "lng" => $location->lng,
            ]);

            $this->syncLocationMetroConnections($createdLocation, $location->metroConnections);
        }
    }

    /**
     * @param array<int, OrganizationMetroConnectionData> $metroConnections
     */
    private function syncLocationMetroConnections(
        OrganizationLocation $location,
        array $metroConnections,
    ): void {
        if ($metroConnections === []) {
            return;
        }

        $deduplicatedConnections = collect($metroConnections)
            ->filter(fn(mixed $item): bool => $item instanceof OrganizationMetroConnectionData)
            ->unique(
                fn(OrganizationMetroConnectionData $item): string => implode(":", [
                    $item->metroStationId,
                    $item->travelMode,
                ]),
            );

        foreach ($deduplicatedConnections as $connection) {
            OrganizationLocationMetroStation::query()->create([
                "organization_location_id" => (string) $location->id,
                "metro_station_id" => $connection->metroStationId,
                "travel_mode" => $connection->travelMode,
                "duration_minutes" => $connection->durationMinutes,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRelatedSnapshot(Organization $organization): array
    {
        return [
            "locations" => $organization
                ->locations()
                ->with("metroConnections")
                ->get()
                ->map(function (OrganizationLocation $location): array {
                    $metroConnections = $location->metroConnections
                        ->map(
                            fn(OrganizationLocationMetroStation $connection): array => [
                                "metro_station_id" => (string) $connection->metro_station_id,
                                "travel_mode" => (string) $connection->travel_mode,
                                "duration_minutes" =>
                                    $connection->duration_minutes === null
                                        ? null
                                        : (int) $connection->duration_minutes,
                            ],
                        )
                        ->all();

                    usort(
                        $metroConnections,
                        fn(array $left, array $right): int => strcmp(
                            $this->encodeSnapshotValue($left),
                            $this->encodeSnapshotValue($right),
                        ),
                    );

                    return [
                        "country_id" => $location->country_id
                            ? (string) $location->country_id
                            : null,
                        "region_id" => $location->region_id ? (string) $location->region_id : null,
                        "city_id" => $location->city_id ? (string) $location->city_id : null,
                        "district_id" => $location->district_id
                            ? (string) $location->district_id
                            : null,
                        "address" => $location->address,
                        "lat" => $location->lat === null ? null : (float) $location->lat,
                        "lng" => $location->lng === null ? null : (float) $location->lng,
                        "metro_connections" => $metroConnections,
                    ];
                })
                ->sortBy(fn(array $location): string => $this->encodeSnapshotValue($location))
                ->values()
                ->all(),
        ];
    }

    private function encodeSnapshotValue(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "";
    }
}
