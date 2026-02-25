<?php

declare(strict_types=1);

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\Country;
use Modules\Geo\Repositories\CountriesRepositoryInterface;
use RuntimeException;

final class CountriesService
{
    public function __construct(private readonly CountriesRepositoryInterface $repository) {}

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

    public function create(array $data): Country
    {
        if (isset($data["iso_code"]) && is_string($data["iso_code"])) {
            $data["iso_code"] = strtoupper($data["iso_code"]);
        }

        return $this->repository->create($data);
    }

    public function findById(string $id): ?Country
    {
        return $this->repository->findById($id);
    }

    public function update(string $id, array $data): Country
    {
        $country = $this->repository->findById($id);
        if (!$country) {
            throw new RuntimeException("Country not found");
        }

        if (isset($data["iso_code"]) && is_string($data["iso_code"])) {
            $data["iso_code"] = strtoupper($data["iso_code"]);
        }

        return $this->repository->update($country, $data);
    }

    public function deleteById(string $id): void
    {
        $country = $this->repository->findById($id);
        if (!$country) {
            throw new RuntimeException("Country not found");
        }

        $this->repository->delete($country);
    }
}
