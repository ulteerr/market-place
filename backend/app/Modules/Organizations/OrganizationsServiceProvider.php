<?php

declare(strict_types=1);

namespace Modules\Organizations;

use App\Support\ModuleServiceProvider;
use Modules\Organizations\Repositories\OrganizationJoinRequestsRepository;
use Modules\Organizations\Repositories\OrganizationJoinRequestsRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationUsersRepository;
use Modules\Organizations\Repositories\OrganizationUsersRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationsRepository;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;

final class OrganizationsServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Organizations";

    public function register(): void
    {
        $this->app->bind(OrganizationsRepositoryInterface::class, OrganizationsRepository::class);
        $this->app->bind(
            OrganizationJoinRequestsRepositoryInterface::class,
            OrganizationJoinRequestsRepository::class,
        );
        $this->app->bind(
            OrganizationUsersRepositoryInterface::class,
            OrganizationUsersRepository::class,
        );
    }
}
