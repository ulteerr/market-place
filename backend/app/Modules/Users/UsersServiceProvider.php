<?php

declare(strict_types=1);

namespace Modules\Users;

use App\Support\ModuleServiceProvider;
use Modules\Users\Repositories\UsersRepositoryInterface;
use Modules\Users\Repositories\UsersRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Repositories\RolesRepository;
use Modules\Users\Repositories\RolesRepositoryInterface;
use Modules\Users\Services\UsersService;

final class UsersServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = 'Users';

    public function register(): void
    {
        $this->app->bind(UsersRepositoryInterface::class, UsersRepository::class);
        $this->app->bind(
            UsersServiceInterface::class,
            UsersService::class
        );
        $this->app->bind(
            RolesRepositoryInterface::class,
            RolesRepository::class
        );
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            Factory::guessFactoryNamesUsing(
                fn(string $modelName) =>
                'Modules\\Users\\Database\\Factories\\' .
                    class_basename($modelName) . 'Factory'
            );
        }
    }
}
