<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\Region;
use Modules\Geo\Repositories\RegionsRepositoryInterface;
use RuntimeException;

final class RegionsService
{
    public function __construct(private readonly RegionsRepositoryInterface $repository) {}

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

    public function create(array $data): Region
    {
        return $this->repository->create($data);
    }

    public function findById(string $id): ?Region
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): Region
    {
        $region = $this->repository->findById($id);
        if (!$region) {
            throw new RuntimeException("Region not found");
        }

        return $this->repository->update($region, $data);
    }

    public function deleteById(string $id): void
    {
        $region = $this->repository->findById($id);
        if (!$region) {
            throw new RuntimeException("Region not found");
        }

        $this->repository->delete($region);
    }
}
