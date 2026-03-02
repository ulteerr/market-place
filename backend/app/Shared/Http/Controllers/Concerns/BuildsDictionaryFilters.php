<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait BuildsDictionaryFilters
{
    /**
     * @param array<int, string> $extraKeys
     * @return array<string, string>
     */
    protected function dictionaryFilters(Request $request, array $extraKeys = []): array
    {
        $filters = [
            "search" => trim((string) $request->query("search", "")),
            "entity_search" => trim((string) $request->query("entity_search", "")),
        ];

        foreach ($extraKeys as $key) {
            $filters[$key] = trim((string) $request->query($key, ""));
        }

        return $filters;
    }
}
