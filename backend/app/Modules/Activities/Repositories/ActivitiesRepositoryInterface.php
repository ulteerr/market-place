<?php

declare(strict_types=1);

namespace Modules\Activities\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Activities\Models\Activity;

interface ActivitiesRepositoryInterface
{
    public function create(array $data): Activity;

    public function update(Activity $activity, array $data): Activity;

    public function findById(string $id): ?Activity;

    public function findByPublicRouteKey(string $publicKey): ?Activity;

    /**
     * @param array<string, mixed> $filters
     */
    public function featured(int $limit = 12, array $filters = []): Collection;

    /**
     * @param array<string, mixed> $filters
     * @return array{items: Collection<int, Activity>, next_cursor: ?string}
     */
    public function feed(int $limit = 20, ?string $cursor = null, array $filters = []): array;

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function delete(Activity $activity): bool;
}
