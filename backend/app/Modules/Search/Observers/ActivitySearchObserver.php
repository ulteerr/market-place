<?php

declare(strict_types=1);

namespace Modules\Search\Observers;

use Modules\Activities\Models\Activity;
use Modules\Search\Indexing\ActivitySearchSyncService;

final class ActivitySearchObserver
{
    public function __construct(private readonly ActivitySearchSyncService $syncService) {}

    public function saved(Activity $activity): void
    {
        $this->syncService->syncActivity($activity);
    }

    public function deleted(Activity $activity): void
    {
        $this->syncService->deleteActivity((string) $activity->id);
    }
}
