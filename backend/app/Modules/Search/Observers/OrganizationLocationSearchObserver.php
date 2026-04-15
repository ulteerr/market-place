<?php

declare(strict_types=1);

namespace Modules\Search\Observers;

use Modules\Organizations\Models\OrganizationLocation;
use Modules\Search\Indexing\ActivitySearchSyncService;

final class OrganizationLocationSearchObserver
{
    public function __construct(private readonly ActivitySearchSyncService $syncService) {}

    public function saved(OrganizationLocation $location): void
    {
        $this->syncService->syncByActivityIds(
            $this->syncService->activityIdsForLocation((string) $location->id),
        );
    }

    public function deleted(OrganizationLocation $location): void
    {
        $this->syncService->syncByActivityIds(
            $this->syncService->activityIdsForLocation((string) $location->id),
        );
    }
}
