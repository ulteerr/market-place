<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Organizations\Models\OrganizationMember;

interface OrganizationMembersRepositoryInterface
{
    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator;

    public function findByIdAndOrganization(
        string $id,
        string $organizationId,
    ): ?OrganizationMember;

    public function create(array $data): OrganizationMember;

    public function update(OrganizationMember $member, array $data): OrganizationMember;

    public function delete(OrganizationMember $member): bool;
}
