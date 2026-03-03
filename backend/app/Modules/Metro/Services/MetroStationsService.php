<?php

declare(strict_types=1);

namespace Modules\Metro\Services;

use App\Shared\DTOs\EntitySearchFiltersDTO;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Metro\DTOs\MetroStationUpsertData;
use Modules\Metro\Models\MetroStation;
use Modules\Metro\Repositories\MetroStationsRepositoryInterface;

final class MetroStationsService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly MetroStationsRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "Metro station not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof MetroStation);
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

    public function create(array $data): MetroStation
    {
        $dto = MetroStationUpsertData::fromArray($data);

        return $this->repository->create($dto->toArray());
    }

    public function findById(string $id): ?MetroStation
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): MetroStation
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof MetroStation);

        $dto = MetroStationUpsertData::fromArray($data);

        return $this->repository->update($entity, $dto->toArray());
    }
}
