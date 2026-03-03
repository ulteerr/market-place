<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use App\Shared\DTOs\EntitySearchFiltersDTO;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\DTOs\RegionUpsertData;
use Modules\Geo\Models\Region;
use Modules\Geo\Repositories\RegionsRepositoryInterface;

final class RegionsService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly RegionsRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "Region not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof Region);
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

    public function create(array $data): Region
    {
        $dto = RegionUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function findById(string $id): ?Region
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): Region
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof Region);

        $dto = RegionUpsertData::fromArray($data);

        return $this->repository->update($entity, $dto->toArray());
    }
}
