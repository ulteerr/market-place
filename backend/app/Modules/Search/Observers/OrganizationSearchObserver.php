<?php

declare(strict_types=1);

namespace Modules\Search\Observers;

use Modules\Organizations\Models\Organization;
use Modules\Search\Indexing\ActivitySearchSyncService;

final class OrganizationSearchObserver
{
    public function __construct(private readonly ActivitySearchSyncService $syncService) {}

    public function saved(Organization $organization): void
    {
        $this->syncService->syncByActivityIds(
            $this->syncService->activityIdsForOrganization((string) $organization->id),
        );
    }

    public function deleted(Organization $organization): void
    {
        $this->syncService->syncByActivityIds(
            $this->syncService->activityIdsForOrganization((string) $organization->id),
        );
    }
}
