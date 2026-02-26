<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\MetroLine;

interface MetroLinesRepositoryInterface
{
    public function list(array $filters = []): Collection;

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function create(array $data): MetroLine;

    public function findById(string $id): ?MetroLine;

    public function update(MetroLine $line, array $data): MetroLine;

    public function delete(MetroLine $line): void;
}
