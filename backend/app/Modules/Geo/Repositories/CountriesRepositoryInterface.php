<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\Country;

interface CountriesRepositoryInterface
{
    public function list(array $filters = []): Collection;

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function create(array $data): Country;

    public function findById(string $id): ?Country;

    public function update(Country $country, array $data): Country;

    public function delete(Country $country): void;
}
