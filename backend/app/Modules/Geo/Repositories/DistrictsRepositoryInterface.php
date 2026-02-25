<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\District;

interface DistrictsRepositoryInterface
{
    public function list(array $filters = []): Collection;

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function create(array $data): District;

    public function findById(string $id): ?District;

    public function update(District $district, array $data): District;

    public function delete(District $district): void;
}
