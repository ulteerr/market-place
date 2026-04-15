<?php

use App\Providers\AdminRoutesServiceProvider;
use Modules\ActionLog\ActionLogServiceProvider;
use Modules\Auth\AuthServiceProvider;
use Modules\Activities\ActivitiesServiceProvider;
use Modules\Categories\CategoriesServiceProvider;
use Modules\Children\ChildrenServiceProvider;
use Modules\ChangeLog\ChangeLogServiceProvider;
use Modules\Files\FilesServiceProvider;
use Modules\Geo\GeoServiceProvider;
use Modules\Metro\MetroServiceProvider;
use Modules\Organizations\OrganizationsServiceProvider;
use Modules\Search\SearchServiceProvider;
use Modules\Users\UsersServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    AdminRoutesServiceProvider::class,
    ActionLogServiceProvider::class,
    AuthServiceProvider::class,
    UsersServiceProvider::class,
    CategoriesServiceProvider::class,
    ActivitiesServiceProvider::class,
    ChildrenServiceProvider::class,
    GeoServiceProvider::class,
    MetroServiceProvider::class,
    OrganizationsServiceProvider::class,
    SearchServiceProvider::class,
    FilesServiceProvider::class,
    ChangeLogServiceProvider::class,
];
