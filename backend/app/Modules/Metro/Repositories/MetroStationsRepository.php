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
        $query = MetroStation::query()
            ->leftJoin("metro_lines", "metro_lines.id", "=", "metro_stations.metro_line_id")
            ->leftJoin("cities", "cities.id", "=", "metro_stations.city_id")
            ->select([
                "metro_stations.id",
                "metro_stations.name",
                "metro_stations.external_id",
                "metro_stations.line_id",
                "metro_stations.geo_lat",
                "metro_stations.geo_lon",
                "metro_stations.is_closed",
                "metro_stations.metro_line_id",
                "metro_stations.city_id",
                "metro_stations.source",
            ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("metro_stations.city_id", $cityId);
        }

        $lineId = trim((string) ($filters["metro_line_id"] ?? ""));
        if ($lineId !== "") {
            $query->where("metro_stations.metro_line_id", $lineId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($searchQuery) use ($search): void {
                $term = "%" . $search . "%";
                $searchQuery
                    ->where("metro_stations.name", "like", $term)
                    ->orWhere("metro_stations.line_id", "like", $term)
                    ->orWhere("metro_lines.name", "like", $term)
                    ->orWhere("metro_lines.line_id", "like", $term)
                    ->orWhere("cities.name", "like", $term);
            });
        }

        return $query->orderBy("metro_stations.name")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = MetroStation::query()
            ->leftJoin("metro_lines", "metro_lines.id", "=", "metro_stations.metro_line_id")
            ->leftJoin("cities", "cities.id", "=", "metro_stations.city_id")
            ->select([
                "metro_stations.id",
                "metro_stations.name",
                "metro_stations.external_id",
                "metro_stations.line_id",
                "metro_stations.geo_lat",
                "metro_stations.geo_lon",
                "metro_stations.is_closed",
                "metro_stations.metro_line_id",
                "metro_stations.city_id",
                "metro_stations.source",
                "metro_stations.created_at",
                "metro_stations.updated_at",
            ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("metro_stations.city_id", $cityId);
        }

        $lineId = trim((string) ($filters["metro_line_id"] ?? ""));
        if ($lineId !== "") {
            $query->where("metro_stations.metro_line_id", $lineId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($searchQuery) use ($search): void {
                $term = "%" . $search . "%";
                $searchQuery
                    ->where("metro_stations.name", "like", $term)
                    ->orWhere("metro_stations.line_id", "like", $term)
                    ->orWhere("metro_lines.name", "like", $term)
                    ->orWhere("metro_lines.line_id", "like", $term)
                    ->orWhere("cities.name", "like", $term);
            });
        }

        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }
        $sortColumns = [
            "id" => "metro_stations.id",
            "name" => "metro_stations.name",
            "line_id" => "metro_stations.line_id",
            // Keep sort key stable for the frontend, but sort by related entity names.
            "metro_line_id" => "metro_lines.name",
            "city_id" => "cities.name",
            "created_at" => "metro_stations.created_at",
        ];
        $sortColumn = $sortColumns[$sortBy] ?? "metro_stations.created_at";

        return $query
            ->orderBy($sortColumn, $sortDir)
            ->orderBy("metro_stations.id")
            ->paginate($perPage);
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
