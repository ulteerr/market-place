<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use App\Shared\DTOs\EntitySearchFiltersDTO;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\DTOs\CountryUpsertData;
use Modules\Geo\Models\Country;
use Modules\Geo\Repositories\CountriesRepositoryInterface;

final class CountriesService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly CountriesRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "Country not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof Country);
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

    public function create(array $data): Country
    {
        $dto = CountryUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function findById(string $id): ?Country
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): Country
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof Country);
        $country = $entity;

        $dto = CountryUpsertData::fromArray($data);

        return $this->repository->update($country, $dto->toArray());
    }
}
