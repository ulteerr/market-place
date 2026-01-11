<?php
declare(strict_types=1);

namespace Modules\Children\Services;

use Modules\Children\Models\Child;
use Modules\Children\Repositories\ChildRepositoryInterface;

final class ChildService
{
    public function __construct(
        private readonly ChildRepositoryInterface $repository
    ) {}

    public function createChild(array $data): Child
    {
        return $this->repository->create($data);
    }

    public function updateChild(Child $child, array $data): Child
    {
        return $this->repository->update($child, $data);
    }

    public function getChildById(string $id): ?Child
    {
        return $this->repository->findById($id);
    }

    public function getChildrenByParent(string $parentId)
    {
        return $this->repository->findByParentId($parentId);
    }

    public function delete(Child $child): bool
    {
        return $this->repository->delete($child);
    }
}