<?php

declare(strict_types=1);

namespace Modules\Categories;

use App\Support\ModuleServiceProvider;
use Modules\Categories\Repositories\CategoriesRepository;
use Modules\Categories\Repositories\CategoriesRepositoryInterface;

final class CategoriesServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Categories";

    public function register(): void
    {
        $this->app->bind(CategoriesRepositoryInterface::class, CategoriesRepository::class);
    }
}
