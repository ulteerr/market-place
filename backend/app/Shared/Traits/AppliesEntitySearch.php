<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

trait AppliesEntitySearch
{
    /**
     * @param array<string, mixed> $filters
     * @param callable(Builder, string): void $fallbackSearch
     */
    protected function applyEntitySearchOrSearch(
        Builder $query,
        array $filters,
        string $entityColumn,
        callable $fallbackSearch,
    ): void {
        $entitySearch = trim((string) ($filters["entity_search"] ?? ""));
        if ($entitySearch !== "") {
            $query->where($entityColumn, "like", "%" . $entitySearch . "%");
            return;
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $fallbackSearch($query, $search);
        }
    }
}
