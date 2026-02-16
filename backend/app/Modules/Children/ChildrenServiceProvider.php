<?php
declare(strict_types=1);

namespace Modules\Children;

use App\Support\ModuleServiceProvider;
use Modules\Children\Repositories\ChildRepositoryInterface;
use Modules\Children\Repositories\ChildRepository;

final class ChildrenServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Children";

    public function register(): void
    {
        $this->app->bind(ChildRepositoryInterface::class, ChildRepository::class);
    }
}
