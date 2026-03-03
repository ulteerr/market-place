<?php

declare(strict_types=1);

namespace Modules\Metro\Services;

use App\Shared\DTOs\EntitySearchFiltersDTO;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Metro\DTOs\MetroLineUpsertData;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Repositories\MetroLinesRepositoryInterface;

final class MetroLinesService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly MetroLinesRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "Metro line not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof MetroLine);
        $this->repository->delete($entity);
    }

    public function list(array $filters = []): Collection
    {
        return $this->repository->list(EntitySearchFiltersDTO::fromArray($filters)->toArray());
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            $perPage,
            EntitySearchFiltersDTO::fromArray($filters)->toArray(),
        );
    }

    public function create(array $data): MetroLine
    {
        $dto = MetroLineUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function findById(string $id): ?MetroLine
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): MetroLine
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof MetroLine);

        $dto = MetroLineUpsertData::fromArray($data);

        return $this->repository->update($entity, $dto->toArray());
    }
}
