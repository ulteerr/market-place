<?php

declare(strict_types=1);

namespace Modules\Search\Commands;

use Illuminate\Console\Command;
use Modules\Activities\Models\Activity;
use Modules\Search\Indexing\ActivitySearchIndexer;

final class ReindexActivitiesSearchCommand extends Command
{
    protected $signature = "search:activities-reindex {--chunk=100}";

    protected $description = "Reindex published activities into the configured search engine";

    public function __construct(private readonly ActivitySearchIndexer $indexer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->indexer->ensureIndex()) {
            $this->error("Search index is unavailable or could not be created.");

            return self::FAILURE;
        }

        $chunk = max(10, (int) $this->option("chunk"));
        $indexed = 0;

        Activity::query()
            ->where("status", "published")
            ->with([
                "organization:id,name",
                "location:id,city_id,address",
                "location.city:id,name",
                "primaryCategory:id,name,slug,parent_id",
                "primaryCategory.parent:id,name,slug",
            ])
            ->orderBy("created_at")
            ->chunk($chunk, function ($activities) use (&$indexed): void {
                $this->indexer->syncMany($activities);
                $indexed += $activities->count();
            });

        $this->info("Indexed activities: {$indexed}");

        return self::SUCCESS;
    }
}
