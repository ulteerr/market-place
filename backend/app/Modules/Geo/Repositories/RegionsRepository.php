<?php

declare(strict_types=1);

namespace Modules\Geo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Geo\Models\Region;

final class RegionsRepository implements RegionsRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = Region::query()->select(["id", "name", "country_id"]);

        $countryId = trim((string) ($filters["country_id"] ?? ""));
        if ($countryId !== "") {
            $query->where("country_id", $countryId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where("name", "like", "%" . $search . "%");
        }

        return $query->orderBy("name")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Region::query()->select(["id", "name", "country_id", "created_at", "updated_at"]);

        $countryId = trim((string) ($filters["country_id"] ?? ""));
        if ($countryId !== "") {
            $query->where("country_id", $countryId);
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

    public function create(array $data): Region
    {
        return Region::query()->create($data);
    }

    public function findById(string $id): ?Region
    {
        return Region::query()->find($id);
    }

    public function update(Region $region, array $data): Region
    {
        $region->update($data);

        return $region;
    }

    public function delete(Region $region): void
    {
        $region->delete();
    }
}
