<?php

declare(strict_types=1);

namespace Modules\Search\Observers;

use Modules\Categories\Models\Category;
use Modules\Search\Indexing\ActivitySearchSyncService;

final class CategorySearchObserver
{
    public function __construct(private readonly ActivitySearchSyncService $syncService) {}

    public function saved(Category $category): void
    {
        $this->syncService->syncByActivityIds($this->affectedIds($category));
    }

    public function deleted(Category $category): void
    {
        $this->syncService->syncByActivityIds($this->affectedIds($category));
    }

    /**
     * @return array<int, string>
     */
    private function affectedIds(Category $category): array
    {
        if ($category->parent_id === null) {
            return $this->syncService->activityIdsForRootCategory((string) $category->id);
        }

        return $this->syncService->activityIdsForCategory((string) $category->id);
    }
}
