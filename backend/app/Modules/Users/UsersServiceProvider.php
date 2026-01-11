<?php
declare(strict_types=1);

namespace Modules\Users;

use Illuminate\Support\ServiceProvider;
use Modules\Users\Repositories\ChildRepository;
use Modules\Users\Repositories\UsersRepositoryInterface;
use Modules\Users\Repositories\ChildRepositoryInterface;
use Modules\Users\Repositories\UsersRepository;

final class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UsersRepositoryInterface::class, UsersRepository::class);
    }

    public function boot(): void
    {
        
    }
}
