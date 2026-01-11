<?php

use Modules\Auth\AuthServiceProvider;
use Modules\Children\ChildrenServiceProvider;
use Modules\Users\UsersServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    AuthServiceProvider::class,
    UsersServiceProvider::class,
    ChildrenServiceProvider::class
];
