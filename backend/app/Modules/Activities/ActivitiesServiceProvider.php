<?php

declare(strict_types=1);

namespace Modules\Activities;

use App\Support\ModuleServiceProvider;
use Modules\Activities\Repositories\ActivitiesRepository;
use Modules\Activities\Repositories\ActivitiesRepositoryInterface;
use Modules\Activities\Repositories\LeadsRepository;
use Modules\Activities\Repositories\LeadsRepositoryInterface;

final class ActivitiesServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Activities";

    public function register(): void
    {
        $this->app->bind(ActivitiesRepositoryInterface::class, ActivitiesRepository::class);
        $this->app->bind(LeadsRepositoryInterface::class, LeadsRepository::class);
    }
}
