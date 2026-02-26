<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\MetroLine;
use Modules\Geo\Repositories\MetroLinesRepositoryInterface;
use RuntimeException;

final class MetroLinesService
{
    public function __construct(private readonly MetroLinesRepositoryInterface $repository) {}

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

    public function create(array $data): MetroLine
    {
        return $this->repository->create($data);
    }

    public function findById(string $id): ?MetroLine
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): MetroLine
    {
        $line = $this->repository->findById($id);
        if (!$line) {
            throw new RuntimeException("Metro line not found");
        }

        return $this->repository->update($line, $data);
    }

    public function deleteById(string $id): void
    {
        $line = $this->repository->findById($id);
        if (!$line) {
            throw new RuntimeException("Metro line not found");
        }

        $this->repository->delete($line);
    }
}
