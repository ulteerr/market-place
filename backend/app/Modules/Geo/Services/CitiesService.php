<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use App\Shared\DTOs\EntitySearchFiltersDTO;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\DTOs\CityUpsertData;
use Modules\Geo\Models\City;
use Modules\Geo\Repositories\CitiesRepositoryInterface;

final class CitiesService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly CitiesRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "City not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof City);
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

    public function create(array $data): City
    {
        $dto = CityUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function findById(string $id): ?City
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): City
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof City);

        $dto = CityUpsertData::fromArray($data);

        return $this->repository->update($entity, $dto->toArray());
    }
}
