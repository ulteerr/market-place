<?php

declare(strict_types=1);

namespace Modules\Children\Services;

use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Children\DTOs\ChildUpsertData;
use Modules\Children\Models\Child;
use Modules\Children\Repositories\ChildRepositoryInterface;

final class ChildService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly ChildRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "Child not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof Child);
        $this->repository->delete($entity);
    }

    public function createChild(array $data): Child
    {
        $dto = ChildUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function create(array $data): Child
    {
        return $this->createChild($data);
    }

    public function updateChild(Child $child, array $data): Child
    {
        $dto = ChildUpsertData::fromArray($data);

        return $this->repository->update($child, $dto->toArray());
    }

    public function update(string $id, array $data): Child
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof Child);

        return $this->updateChild($entity, $data);
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
}
