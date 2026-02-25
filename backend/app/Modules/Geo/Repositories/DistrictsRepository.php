<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\District;

final class DistrictsRepository implements DistrictsRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = District::query()->select(["id", "name", "city_id"]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("city_id", $cityId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where("name", "like", "%" . $search . "%");
        }

        return $query->orderBy("name")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = District::query()->select(["id", "name", "city_id", "created_at", "updated_at"]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("city_id", $cityId);
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

    public function create(array $data): District
    {
        return District::query()->create($data);
    }

    public function findById(string $id): ?District
    {
        return District::query()->find($id);
    }

    public function update(District $district, array $data): District
    {
        $district->update($data);

        return $district;
    }

    public function delete(District $district): void
    {
        $district->delete();
    }
}
