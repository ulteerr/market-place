<?php

declare(strict_types=1);

namespace Modules\ChangeLog;

use App\Support\ModuleServiceProvider;
use Modules\ChangeLog\Services\ChangeLogContext;

final class ChangeLogServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "ChangeLog";

    public function register(): void
    {
        $this->app->scoped(ChangeLogContext::class, fn() => new ChangeLogContext());
    }
}
