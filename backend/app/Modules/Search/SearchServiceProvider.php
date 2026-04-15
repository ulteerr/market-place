<?php

declare(strict_types=1);

namespace Modules\Search;

use App\Support\ModuleServiceProvider;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Geo\Models\City;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Search\Contracts\SearchEngineInterface;
use Modules\Search\Commands\ReindexActivitiesSearchCommand;
use Modules\Search\Engines\ElasticsearchSearchEngine;
use Modules\Search\Engines\NullSearchEngine;
use Modules\Search\Indexing\ActivitySearchDocumentFactory;
use Modules\Search\Indexing\ActivitySearchIndexer;
use Modules\Search\Indexing\ActivitySearchSyncService;
use Modules\Search\Observers\ActivitySearchObserver;
use Modules\Search\Observers\CategorySearchObserver;
use Modules\Search\Observers\CitySearchObserver;
use Modules\Search\Observers\OrganizationLocationSearchObserver;
use Modules\Search\Observers\OrganizationSearchObserver;

final class SearchServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Search";

    public function register(): void
    {
        $this->app->singleton(ActivitySearchDocumentFactory::class);
        $this->app->singleton(ActivitySearchIndexer::class);
        $this->app->singleton(ActivitySearchSyncService::class);
        $this->app->bind(SearchEngineInterface::class, function () {
            $enabled = (bool) config("search.enabled", true);
            $driver = (string) config("search.default", "elasticsearch");
            $indexPrefix = (string) config("search.index_prefix", "marketplace_");
            $elasticsearchEnabled = (bool) config("search.drivers.elasticsearch.enabled", true);

            if (!$enabled) {
                return new NullSearchEngine();
            }

            return match ($driver) {
                "elasticsearch" => $elasticsearchEnabled
                    ? new ElasticsearchSearchEngine(
                        (array) config("search.drivers.elasticsearch", []),
                        $indexPrefix,
                        $enabled,
                    )
                    : new NullSearchEngine(),
                default => new NullSearchEngine(),
            };
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ReindexActivitiesSearchCommand::class]);
        }
    }

    public function boot(): void
    {
        parent::boot();

        Activity::observe(ActivitySearchObserver::class);
        Category::observe(CategorySearchObserver::class);
        Organization::observe(OrganizationSearchObserver::class);
        OrganizationLocation::observe(OrganizationLocationSearchObserver::class);
        City::observe(CitySearchObserver::class);
    }
}
