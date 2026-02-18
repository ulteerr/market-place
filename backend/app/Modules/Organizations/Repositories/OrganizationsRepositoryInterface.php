<?php

declare(strict_types=1);

namespace Modules\Organizations\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Organizations\Models\Organization;

interface OrganizationsRepositoryInterface
{
    public function create(array $data): Organization;

    public function update(Organization $organization, array $data): Organization;

    public function findById(string $id): ?Organization;

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function paginateForUser(
        string $userId,
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function delete(Organization $organization): bool;
}
