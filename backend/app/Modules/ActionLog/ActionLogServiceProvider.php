<?php

declare(strict_types=1);

namespace Modules\ActionLog;

use App\Support\ModuleServiceProvider;
use Modules\ActionLog\Services\ActionLogService;

final class ActionLogServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "ActionLog";

    public function register(): void
    {
        $this->app->scoped(ActionLogService::class, fn() => new ActionLogService());
    }
}
