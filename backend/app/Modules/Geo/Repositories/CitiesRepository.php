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
        $query = City::query()
            ->with(["country:id,name", "region:id,name"])
            ->leftJoin("countries", "countries.id", "=", "cities.country_id")
            ->leftJoin("regions", "regions.id", "=", "cities.region_id")
            ->select(["cities.id", "cities.name", "cities.country_id", "cities.region_id"]);

        $countryId = trim((string) ($filters["country_id"] ?? ""));
        if ($countryId !== "") {
            $query->where("cities.country_id", $countryId);
        }

        $regionId = trim((string) ($filters["region_id"] ?? ""));
        if ($regionId !== "") {
            $query->where("cities.region_id", $regionId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($searchQuery) use ($search): void {
                $term = "%" . $search . "%";
                $searchQuery
                    ->where("cities.name", "like", $term)
                    ->orWhere("countries.name", "like", $term)
                    ->orWhere("regions.name", "like", $term);
            });
        }

        return $query->orderBy("cities.name")->orderBy("cities.id")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = City::query()
            ->with(["country:id,name", "region:id,name"])
            ->leftJoin("countries", "countries.id", "=", "cities.country_id")
            ->leftJoin("regions", "regions.id", "=", "cities.region_id")
            ->select([
                "cities.id",
                "cities.name",
                "cities.country_id",
                "cities.region_id",
                "cities.created_at",
                "cities.updated_at",
            ]);

        $countryId = trim((string) ($filters["country_id"] ?? ""));
        if ($countryId !== "") {
            $query->where("cities.country_id", $countryId);
        }

        $regionId = trim((string) ($filters["region_id"] ?? ""));
        if ($regionId !== "") {
            $query->where("cities.region_id", $regionId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($searchQuery) use ($search): void {
                $term = "%" . $search . "%";
                $searchQuery
                    ->where("cities.name", "like", $term)
                    ->orWhere("countries.name", "like", $term)
                    ->orWhere("regions.name", "like", $term);
            });
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }
        $sortColumns = [
            "id" => "cities.id",
            "name" => "cities.name",
            "country_id" => "countries.name",
            "region_id" => "regions.name",
            "created_at" => "cities.created_at",
        ];
        $sortColumn = $sortColumns[$sortBy] ?? "cities.created_at";

        return $query->orderBy($sortColumn, $sortDir)->orderBy("cities.id")->paginate($perPage);
    }

    public function create(array $data): City
    {
        return City::query()
            ->create($data)
            ->load(["country:id,name", "region:id,name"]);
    }

    public function findById(string $id): ?City
    {
        return City::query()
            ->with(["country:id,name", "region:id,name"])
            ->find($id);
    }

    public function update(City $city, array $data): City
    {
        $city->update($data);

        return $city->refresh()->load(["country:id,name", "region:id,name"]);
    }

    public function delete(City $city): void
    {
        $city->delete();
    }
}
