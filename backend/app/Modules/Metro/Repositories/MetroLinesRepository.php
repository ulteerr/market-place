<?php

declare(strict_types=1);

namespace Modules\Metro\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Metro\Models\MetroLine;

final class MetroLinesRepository implements MetroLinesRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = MetroLine::query()
            ->with(["city:id,name"])
            ->leftJoin("cities", "cities.id", "=", "metro_lines.city_id")
            ->select([
                "metro_lines.id",
                "metro_lines.name",
                "metro_lines.external_id",
                "metro_lines.line_id",
                "metro_lines.color",
                "metro_lines.city_id",
                "metro_lines.source",
            ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("metro_lines.city_id", $cityId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($searchQuery) use ($search): void {
                $term = "%" . $search . "%";
                $searchQuery
                    ->where("metro_lines.name", "like", $term)
                    ->orWhere("metro_lines.line_id", "like", $term)
                    ->orWhere("cities.name", "like", $term);
            });
        }

        return $query->orderBy("metro_lines.name")->orderBy("metro_lines.id")->get();
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = MetroLine::query()
            ->with(["city:id,name"])
            ->leftJoin("cities", "cities.id", "=", "metro_lines.city_id")
            ->select([
                "metro_lines.id",
                "metro_lines.name",
                "metro_lines.external_id",
                "metro_lines.line_id",
                "metro_lines.color",
                "metro_lines.city_id",
                "metro_lines.source",
                "metro_lines.created_at",
                "metro_lines.updated_at",
            ]);

        $cityId = trim((string) ($filters["city_id"] ?? ""));
        if ($cityId !== "") {
            $query->where("metro_lines.city_id", $cityId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($searchQuery) use ($search): void {
                $term = "%" . $search . "%";
                $searchQuery
                    ->where("metro_lines.name", "like", $term)
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
            "id" => "metro_lines.id",
            "name" => "metro_lines.name",
            "line_id" => "metro_lines.line_id",
            "city_id" => "cities.name",
            "created_at" => "metro_lines.created_at",
        ];
        $sortColumn = $sortColumns[$sortBy] ?? "metro_lines.created_at";

        return $query
            ->orderBy($sortColumn, $sortDir)
            ->orderBy("metro_lines.id")
            ->paginate($perPage);
    }

    public function create(array $data): MetroLine
    {
        return MetroLine::query()
            ->create($data)
            ->load(["city:id,name"]);
    }

    public function findById(string $id): ?MetroLine
    {
        return MetroLine::query()
            ->with(["city:id,name"])
            ->find($id);
    }

    public function update(MetroLine $line, array $data): MetroLine
    {
        $line->update($data);

        return $line->refresh()->load(["city:id,name"]);
    }

    public function delete(MetroLine $line): void
    {
        $line->delete();
    }
}
