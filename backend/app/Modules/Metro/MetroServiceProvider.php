<?php

declare(strict_types=1);

namespace Modules\Metro;

use App\Support\ModuleServiceProvider;
use Modules\Metro\Console\Commands\ImportMetroFromDadataCommand;
use Modules\Metro\Repositories\MetroLinesRepository;
use Modules\Metro\Repositories\MetroLinesRepositoryInterface;
use Modules\Metro\Repositories\MetroStationsRepository;
use Modules\Metro\Repositories\MetroStationsRepositoryInterface;

final class MetroServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Metro";

    public function register(): void
    {
        $this->app->bind(MetroLinesRepositoryInterface::class, MetroLinesRepository::class);
        $this->app->bind(MetroStationsRepositoryInterface::class, MetroStationsRepository::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ImportMetroFromDadataCommand::class]);
        }
    }
}
