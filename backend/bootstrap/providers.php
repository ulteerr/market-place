<?php

use App\Providers\AdminRoutesServiceProvider;
use Modules\ActionLog\ActionLogServiceProvider;
use Modules\Auth\AuthServiceProvider;
use Modules\Children\ChildrenServiceProvider;
use Modules\ChangeLog\ChangeLogServiceProvider;
use Modules\Files\FilesServiceProvider;
use Modules\Geo\GeoServiceProvider;
use Modules\Metro\MetroServiceProvider;
use Modules\Organizations\OrganizationsServiceProvider;
use Modules\Users\UsersServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    AdminRoutesServiceProvider::class,
    ActionLogServiceProvider::class,
    AuthServiceProvider::class,
    UsersServiceProvider::class,
    ChildrenServiceProvider::class,
    GeoServiceProvider::class,
    MetroServiceProvider::class,
    OrganizationsServiceProvider::class,
    FilesServiceProvider::class,
    ChangeLogServiceProvider::class,
];
