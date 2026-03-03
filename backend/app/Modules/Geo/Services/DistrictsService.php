<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use App\Shared\DTOs\EntitySearchFiltersDTO;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\DTOs\DistrictUpsertData;
use Modules\Geo\Models\District;
use Modules\Geo\Repositories\DistrictsRepositoryInterface;

final class DistrictsService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly DistrictsRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "District not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof District);
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

    public function create(array $data): District
    {
        $dto = DistrictUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function findById(string $id): ?District
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): District
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof District);

        $dto = DistrictUpsertData::fromArray($data);

        return $this->repository->update($entity, $dto->toArray());
    }
}
