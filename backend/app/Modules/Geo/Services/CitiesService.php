<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\City;
use Modules\Geo\Repositories\CitiesRepositoryInterface;
use RuntimeException;

final class CitiesService
{
    public function __construct(private readonly CitiesRepositoryInterface $repository) {}

    public function list(array $filters = []): Collection
    {
        return $this->repository->list($filters);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $filters);
    }

    public function create(array $data): City
    {
        return $this->repository->create($data);
    }

    public function findById(string $id): ?City
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): City
    {
        $city = $this->repository->findById($id);
        if (!$city) {
            throw new RuntimeException("City not found");
        }

        return $this->repository->update($city, $data);
    }

    public function deleteById(string $id): void
    {
        $city = $this->repository->findById($id);
        if (!$city) {
            throw new RuntimeException("City not found");
        }

        $this->repository->delete($city);
    }
}
