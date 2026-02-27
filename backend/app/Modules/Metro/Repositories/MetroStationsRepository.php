<?php

declare(strict_types=1);

namespace Modules\Metro\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Metro\Models\MetroStation;

final class MetroStationsRepository implements MetroStationsRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = MetroStation::query()->select([
            "id",
            "name",
            "external_id",
            "line_id",
            "geo_lat",
            "geo_lon",
            "is_closed",
            "metro_line_id",
            "city_id",
            "source",
        ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("city_id", $cityId);
        }

        $lineId = trim((string) ($filters["metro_line_id"] ?? ""));
        if ($lineId !== "") {
            $query->where("metro_line_id", $lineId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where("name", "like", "%" . $search . "%");
        }

        return $query->orderBy("name")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = MetroStation::query()->select([
            "id",
            "name",
            "external_id",
            "line_id",
            "geo_lat",
            "geo_lon",
            "is_closed",
            "metro_line_id",
            "city_id",
            "source",
            "created_at",
            "updated_at",
        ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("city_id", $cityId);
        }

        $lineId = trim((string) ($filters["metro_line_id"] ?? ""));
        if ($lineId !== "") {
            $query->where("metro_line_id", $lineId);
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

    public function create(array $data): MetroStation
    {
        return MetroStation::query()->create($data);
    }

    public function findById(string $id): ?MetroStation
    {
        return MetroStation::query()->find($id);
    }

    public function update(MetroStation $station, array $data): MetroStation
    {
        $station->update($data);

        return $station;
    }

    public function delete(MetroStation $station): void
    {
        $station->delete();
    }
}
