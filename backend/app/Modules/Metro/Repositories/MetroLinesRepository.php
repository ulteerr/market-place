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
        $query = MetroLine::query()->select([
            "id",
            "name",
            "external_id",
            "line_id",
            "color",
            "city_id",
            "source",
        ]);

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
        $query = MetroLine::query()->select([
            "id",
            "name",
            "external_id",
            "line_id",
            "color",
            "city_id",
            "source",
            "created_at",
            "updated_at",
        ]);

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

    public function create(array $data): MetroLine
    {
        return MetroLine::query()->create($data);
    }

    public function findById(string $id): ?MetroLine
    {
        return MetroLine::query()->find($id);
    }

    public function update(MetroLine $line, array $data): MetroLine
    {
        $line->update($data);

        return $line;
    }

    public function delete(MetroLine $line): void
    {
        $line->delete();
    }
}
