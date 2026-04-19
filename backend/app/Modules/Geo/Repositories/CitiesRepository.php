<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use App\Shared\Traits\AppliesEntitySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Geo\Models\City;

final class CitiesRepository implements CitiesRepositoryInterface
{
    use AppliesEntitySearch;

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

        $this->applyEntitySearchOrSearch($query, $filters, "cities.name", function (
            $searchQuery,
            string $search,
        ): void {
            $term = "%" . $search . "%";
            $searchQuery
                ->where("cities.name", "like", $term)
                ->orWhere("countries.name", "like", $term)
                ->orWhere("regions.name", "like", $term);
        });

        return $query->orderBy("cities.name")->orderBy("cities.id")->get();
    }

    public function publicOptions(string $search = "", int $limit = 20): Collection
    {
        $normalizedSearch = trim($search);
        $normalizedLimit = max(1, min($limit, 50));

        $query = City::query()
            ->with(["country:id,name", "region:id,name"])
            ->leftJoin("countries", "countries.id", "=", "cities.country_id")
            ->leftJoin("regions", "regions.id", "=", "cities.region_id")
            ->select(["cities.id", "cities.name", "cities.country_id", "cities.region_id"]);

        if ($normalizedSearch !== "") {
            $this->applyEntitySearchOrSearch(
                $query,
                [
                    "search" => $normalizedSearch,
                    "entity_search" => $normalizedSearch,
                ],
                "cities.name",
                function ($searchQuery, string $search): void {
                    $term = "%" . $search . "%";
                    $searchQuery
                        ->where("cities.name", "like", $term)
                        ->orWhere("countries.name", "like", $term)
                        ->orWhere("regions.name", "like", $term);
                },
            );
        }

        return $query->orderBy("cities.name")->orderBy("cities.id")->limit($normalizedLimit)->get();
    }

    public function firstById(): ?City
    {
        return City::query()
            ->with(["country:id,name", "region:id,name"])
            ->orderBy("id")
            ->first();
    }

    public function findByNames(
        string $cityName,
        ?string $countryName = null,
        ?string $regionName = null,
    ): ?City {
        $normalizedCityName = $this->normalizeName($cityName);
        if ($normalizedCityName === "") {
            return null;
        }

        $normalizedCountryName = $this->normalizeName((string) $countryName);
        $normalizedRegionName = $this->normalizeName((string) $regionName);

        $candidates = City::query()
            ->with(["country:id,name", "region:id,name"])
            ->whereIn("name", $this->exactNameVariants($cityName))
            ->orderBy("id")
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $matchCountryAndRegion = fn(City $city): bool => $this->normalizeName(
            (string) $city->country?->name,
        ) === $normalizedCountryName &&
            $this->normalizeName((string) $city->region?->name) === $normalizedRegionName;

        $matchCountryOnly = fn(City $city): bool => $this->normalizeName(
            (string) $city->country?->name,
        ) === $normalizedCountryName;

        if ($normalizedCountryName !== "" && $normalizedRegionName !== "") {
            $matched = $candidates->first($matchCountryAndRegion);
            if ($matched) {
                return $matched;
            }
        }

        if ($normalizedCountryName !== "") {
            $matched = $candidates->first($matchCountryOnly);
            if ($matched) {
                return $matched;
            }
        }

        return $candidates->first();
    }

    /**
     * @return array<int, string>
     */
    private function exactNameVariants(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === "") {
            return [];
        }

        return array_values(
            array_unique([
                $trimmed,
                Str::lower($trimmed),
                mb_strtoupper($trimmed, "UTF-8"),
                mb_convert_case($trimmed, MB_CASE_TITLE, "UTF-8"),
            ]),
        );
    }

    private function normalizeName(string $value): string
    {
        return Str::lower(trim($value));
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

        $this->applyEntitySearchOrSearch($query, $filters, "cities.name", function (
            $searchQuery,
            string $search,
        ): void {
            $term = "%" . $search . "%";
            $searchQuery
                ->where("cities.name", "like", $term)
                ->orWhere("countries.name", "like", $term)
                ->orWhere("regions.name", "like", $term);
        });

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
