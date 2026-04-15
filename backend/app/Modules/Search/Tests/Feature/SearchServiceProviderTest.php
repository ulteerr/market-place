<?php

declare(strict_types=1);

namespace Modules\Search\Tests\Feature;

use Modules\Search\Contracts\SearchEngineInterface;
use Modules\Search\Engines\ElasticsearchSearchEngine;
use Modules\Search\Engines\NullSearchEngine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SearchServiceProviderTest extends TestCase
{
    #[Test]
    public function it_resolves_elasticsearch_driver_via_interface(): void
    {
        config()->set("search.enabled", true);
        config()->set("search.default", "elasticsearch");

        $engine = $this->app->make(SearchEngineInterface::class);

        $this->assertInstanceOf(ElasticsearchSearchEngine::class, $engine);
    }

    #[Test]
    public function it_falls_back_to_null_engine_when_search_is_disabled(): void
    {
        config()->set("search.enabled", false);
        config()->set("search.default", "elasticsearch");

        $engine = $this->app->make(SearchEngineInterface::class);

        $this->assertInstanceOf(NullSearchEngine::class, $engine);
    }

    #[Test]
    public function it_falls_back_to_null_engine_when_elasticsearch_driver_is_disabled(): void
    {
        config()->set("search.enabled", true);
        config()->set("search.default", "elasticsearch");
        config()->set("search.drivers.elasticsearch.enabled", false);

        $engine = $this->app->make(SearchEngineInterface::class);

        $this->assertInstanceOf(NullSearchEngine::class, $engine);
    }
}
