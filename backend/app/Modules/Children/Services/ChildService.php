<?php
declare(strict_types=1);

namespace Modules\Children\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;
use Modules\Children\Models\Child;
use Modules\Children\Repositories\ChildRepositoryInterface;

final class ChildService
{
    public function __construct(private readonly ChildRepositoryInterface $repository) {}

    public function createChild(array $data): Child
    {
        return $this->repository->create($data);
    }

    public function create(array $data): Child
    {
        return $this->createChild($data);
    }

    public function updateChild(Child $child, array $data): Child
    {
        return $this->repository->update($child, $data);
    }

    public function update(string $id, array $data): Child
    {
        $child = $this->getChildById($id);
        if (!$child) {
            throw new RuntimeException("Child not found");
        }

        return $this->updateChild($child, $data);
    }

    public function getChildById(string $id): ?Child
    {
        return $this->repository->findById($id);
    }

    public function findById(string $id): ?Child
    {
        return $this->getChildById($id);
    }

    public function getChildrenByParent(string $parentId): Collection
    {
        return $this->repository->findByUserId($parentId);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $with, $filters);
    }

    public function delete(Child $child): bool
    {
        return $this->repository->delete($child);
    }

    public function deleteById(string $id): void
    {
        $child = $this->getChildById($id);
        if (!$child) {
            throw new RuntimeException("Child not found");
        }

        $this->delete($child);
    }
}
