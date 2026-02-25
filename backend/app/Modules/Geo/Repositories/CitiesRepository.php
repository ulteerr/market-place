<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\City;

final class CitiesRepository implements CitiesRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = City::query()->select(["id", "name", "country_id", "region_id"]);

        $countryId = trim((string) ($filters["country_id"] ?? ""));
        if ($countryId !== "") {
            $query->where("country_id", $countryId);
        }

        $regionId = trim((string) ($filters["region_id"] ?? ""));
        if ($regionId !== "") {
            $query->where("region_id", $regionId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where("name", "like", "%" . $search . "%");
        }

        return $query->orderBy("name")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = City::query()->select([
            "id",
            "name",
            "country_id",
            "region_id",
            "created_at",
            "updated_at",
        ]);

        $countryId = trim((string) ($filters["country_id"] ?? ""));
        if ($countryId !== "") {
            $query->where("country_id", $countryId);
        }

        $regionId = trim((string) ($filters["region_id"] ?? ""));
        if ($regionId !== "") {
            $query->where("region_id", $regionId);
        }

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

    public function create(array $data): City
    {
        return City::query()->create($data);
    }

    public function findById(string $id): ?City
    {
        return City::query()->find($id);
    }

    public function update(City $city, array $data): City
    {
        $city->update($data);

        return $city;
    }

    public function delete(City $city): void
    {
        $city->delete();
    }
}
