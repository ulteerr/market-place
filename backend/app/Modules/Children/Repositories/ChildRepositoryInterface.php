<?php
declare(strict_types=1);

namespace Modules\Children\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Children\Models\Child;

interface ChildRepositoryInterface
{
    public function create(array $data): Child;

    public function update(Child $child, array $data): Child;

    public function findById(string $id): ?Child;

    public function findByUserId(string $userId): Collection;

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function delete(Child $child): bool;
}
