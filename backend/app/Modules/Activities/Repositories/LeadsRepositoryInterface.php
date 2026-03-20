<?php

declare(strict_types=1);

namespace Modules\Activities\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Activities\Models\Lead;

interface LeadsRepositoryInterface
{
    public function create(array $data): Lead;

    public function update(Lead $lead, array $data): Lead;

    public function findById(string $id): ?Lead;

    public function findByIdAndOrganization(string $leadId, string $organizationId): ?Lead;

    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator;

    public function paginateAdmin(int $perPage = 20, array $filters = []): LengthAwarePaginator;
}
