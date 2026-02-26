<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\MetroStation;
use Modules\Geo\Repositories\MetroStationsRepositoryInterface;
use RuntimeException;

final class MetroStationsService
{
    public function __construct(private readonly MetroStationsRepositoryInterface $repository) {}

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

    public function create(array $data): MetroStation
    {
        return $this->repository->create($data);
    }

    public function findById(string $id): ?MetroStation
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): MetroStation
    {
        $station = $this->repository->findById($id);
        if (!$station) {
            throw new RuntimeException("Metro station not found");
        }

        return $this->repository->update($station, $data);
    }

    public function deleteById(string $id): void
    {
        $station = $this->repository->findById($id);
        if (!$station) {
            throw new RuntimeException("Metro station not found");
        }

        $this->repository->delete($station);
    }
}
