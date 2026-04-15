<?php

declare(strict_types=1);

namespace Modules\Search\Contracts;

interface SearchEngineInterface
{
    public function isAvailable(): bool;

    /**
     * @param array<string, mixed> $definition
     */
    public function ensureIndex(string $index, array $definition = []): bool;

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    public function upsertDocuments(string $index, array $documents): bool;

    /**
     * @param array<int, string> $ids
     */
    public function deleteDocuments(string $index, array $ids): bool;

    /**
     * @param array<string, mixed> $options
     * @return array{
     *   total: int,
     *   items: array<int, array<string, mixed>>,
     *   meta?: array<string, mixed>
     * }
     */
    public function search(string $index, string $query, array $options = []): array;

    /**
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    public function suggest(string $index, string $query, array $options = []): array;
}
