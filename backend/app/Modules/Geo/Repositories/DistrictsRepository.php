<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use App\Shared\Traits\AppliesEntitySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\District;

final class DistrictsRepository implements DistrictsRepositoryInterface
{
    use AppliesEntitySearch;

    public function list(array $filters = []): Collection
    {
        $query = District::query()
            ->with(["city:id,name"])
            ->leftJoin("cities", "cities.id", "=", "districts.city_id")
            ->select(["districts.id", "districts.name", "districts.city_id"]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("districts.city_id", $cityId);
        }

        $this->applyEntitySearchOrSearch($query, $filters, "districts.name", function (
            $searchQuery,
            string $search,
        ): void {
            $term = "%" . $search . "%";
            $searchQuery
                ->where("districts.name", "like", $term)
                ->orWhere("cities.name", "like", $term);
        });

        return $query->orderBy("districts.name")->orderBy("districts.id")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = District::query()
            ->with(["city:id,name"])
            ->leftJoin("cities", "cities.id", "=", "districts.city_id")
            ->select([
                "districts.id",
                "districts.name",
                "districts.city_id",
                "districts.created_at",
                "districts.updated_at",
            ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("districts.city_id", $cityId);
        }

        $this->applyEntitySearchOrSearch($query, $filters, "districts.name", function (
            $searchQuery,
            string $search,
        ): void {
            $term = "%" . $search . "%";
            $searchQuery
                ->where("districts.name", "like", $term)
                ->orWhere("cities.name", "like", $term);
        });

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }
        $sortColumns = [
            "id" => "districts.id",
            "name" => "districts.name",
            "city_id" => "cities.name",
            "created_at" => "districts.created_at",
        ];
        $sortColumn = $sortColumns[$sortBy] ?? "districts.created_at";

        return $query->orderBy($sortColumn, $sortDir)->orderBy("districts.id")->paginate($perPage);
    }

    public function create(array $data): District
    {
        return District::query()
            ->create($data)
            ->load(["city:id,name"]);
    }

    public function findById(string $id): ?District
    {
        return District::query()
            ->with(["city:id,name"])
            ->find($id);
    }

    public function update(District $district, array $data): District
    {
        $district->update($data);

        return $district->refresh()->load(["city:id,name"]);
    }

    public function delete(District $district): void
    {
        $district->delete();
    }
}
