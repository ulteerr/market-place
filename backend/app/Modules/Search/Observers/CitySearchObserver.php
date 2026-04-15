<?php

declare(strict_types=1);

namespace Modules\Search\Observers;

use Modules\Geo\Models\City;
use Modules\Search\Indexing\ActivitySearchSyncService;

final class CitySearchObserver
{
    public function __construct(private readonly ActivitySearchSyncService $syncService) {}

    public function saved(City $city): void
    {
        $this->syncService->syncByActivityIds(
            $this->syncService->activityIdsForCity((string) $city->id),
        );
    }

    public function deleted(City $city): void
    {
        $this->syncService->syncByActivityIds(
            $this->syncService->activityIdsForCity((string) $city->id),
        );
    }
}
