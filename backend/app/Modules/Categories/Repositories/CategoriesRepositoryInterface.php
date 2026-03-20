<?php

declare(strict_types=1);

namespace Modules\Categories\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Categories\Models\Category;

interface CategoriesRepositoryInterface
{
    public function create(array $data): Category;

    public function update(Category $category, array $data): Category;

    public function findById(string $id): ?Category;

    public function findRoots(bool $onlyActive = false): Collection;

    public function findChildren(string $parentId, bool $onlyActive = false): Collection;

    public function getTree(bool $onlyActive = false): Collection;

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function delete(Category $category): bool;
}
