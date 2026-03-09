<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Organizations\Models\OrganizationUser;

interface OrganizationUsersRepositoryInterface
{
    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator;

    public function findByIdAndOrganization(string $id, string $organizationId): ?OrganizationUser;

    public function create(array $data): OrganizationUser;

    public function update(OrganizationUser $member, array $data): OrganizationUser;

    public function delete(OrganizationUser $member): bool;
}
