<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Organizations\Models\OrganizationJoinRequest;

interface OrganizationJoinRequestsRepositoryInterface
{
    public function create(array $data): OrganizationJoinRequest;

    public function findByIdAndOrganization(
        string $id,
        string $organizationId,
    ): ?OrganizationJoinRequest;

    public function findPendingByOrganizationAndUser(
        string $organizationId,
        string $userId,
    ): ?OrganizationJoinRequest;

    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator;

    public function paginateForOrganizationAndUser(
        string $organizationId,
        string $userId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator;

    public function update(OrganizationJoinRequest $request, array $data): OrganizationJoinRequest;
}
