<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\District;
use Modules\Geo\Repositories\DistrictsRepositoryInterface;
use RuntimeException;

final class DistrictsService
{
    public function __construct(private readonly DistrictsRepositoryInterface $repository) {}

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

    public function create(array $data): District
    {
        return $this->repository->create($data);
    }

    public function findById(string $id): ?District
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): District
    {
        $district = $this->repository->findById($id);
        if (!$district) {
            throw new RuntimeException("District not found");
        }

        return $this->repository->update($district, $data);
    }

    public function deleteById(string $id): void
    {
        $district = $this->repository->findById($id);
        if (!$district) {
            throw new RuntimeException("District not found");
        }

        $this->repository->delete($district);
    }
}
