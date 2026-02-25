<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\Country;

final class CountriesRepository implements CountriesRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = Country::query()->select(["id", "name", "iso_code"]);

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where("name", "like", "%" . $search . "%");
        }

        return $query->orderBy("name")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Country::query()->select(["id", "name", "iso_code", "created_at", "updated_at"]);

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where("name", "like", "%" . $search . "%");
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }
        if (!in_array($sortBy, ["id", "name", "created_at"], true)) {
            $sortBy = "created_at";
        }

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function create(array $data): Country
    {
        return Country::query()->create($data);
    }

    public function findById(string $id): ?Country
    {
        return Country::query()->find($id);
    }

    public function update(Country $country, array $data): Country
    {
        $country->update($data);

        return $country;
    }

    public function delete(Country $country): void
    {
        $country->delete();
    }
}
