<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Users\Services\UsersService;
use Modules\Users\Http\Responses\UserResponseFactory;
use Modules\Users\Http\Requests\CreateAdminUserRequest;
use Modules\Users\Http\Requests\UpdateAdminUserRequest;

final class AdminUserController extends AdminCrudController
{
    public function __construct(
        private readonly UsersService $usersService
    ) {}

    protected function service(): object
    {
        return $this->usersService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminUserRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminUserRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return UserResponseFactory::class;
    }
}
