<?php

use App\Providers\AdminRoutesServiceProvider;
use Modules\Auth\AuthServiceProvider;
use Modules\Children\ChildrenServiceProvider;
use Modules\ChangeLog\ChangeLogServiceProvider;
use Modules\Files\FilesServiceProvider;
use Modules\Users\UsersServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    AdminRoutesServiceProvider::class,
    AuthServiceProvider::class,
    UsersServiceProvider::class,
    ChildrenServiceProvider::class,
    FilesServiceProvider::class,
    ChangeLogServiceProvider::class,
];
