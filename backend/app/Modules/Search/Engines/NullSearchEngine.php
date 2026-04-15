<?php

declare(strict_types=1);

namespace Modules\Search\Engines;

use Modules\Search\Contracts\SearchEngineInterface;

final class NullSearchEngine implements SearchEngineInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function ensureIndex(string $index, array $definition = []): bool
    {
        return false;
    }

    public function upsertDocuments(string $index, array $documents): bool
    {
        return false;
    }

    public function deleteDocuments(string $index, array $ids): bool
    {
        return false;
    }

    public function search(string $index, string $query, array $options = []): array
    {
        return [
            "total" => 0,
            "items" => [],
        ];
    }

    public function suggest(string $index, string $query, array $options = []): array
    {
        return [];
    }
}
