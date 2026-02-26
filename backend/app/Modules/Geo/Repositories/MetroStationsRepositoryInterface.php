<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\MetroStation;

interface MetroStationsRepositoryInterface
{
    public function list(array $filters = []): Collection;

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function create(array $data): MetroStation;

    public function findById(string $id): ?MetroStation;

    public function update(MetroStation $station, array $data): MetroStation;

    public function delete(MetroStation $station): void;
}
