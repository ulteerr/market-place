<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Users\Services\RolesService;
use Modules\Users\Http\Requests\CreateRoleRequest;
use Modules\Users\Http\Requests\UpdateRoleRequest;
use Modules\Users\Http\Responses\RoleResponseFactory;

final class AdminRoleController extends AdminCrudController
{
    public function __construct(
        private readonly RolesService $rolesService
    ) {}

    protected function service(): object
    {
        return $this->rolesService;
    }

    protected function createRequestClass(): string
    {
        return CreateRoleRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateRoleRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return RoleResponseFactory::class;
    }

    protected function createMethod(): string
    {
        return 'createRole';
    }

    protected function findMethod(): string
    {
        return 'getRoleById';
    }

    protected function updateMethod(): string
    {
        return 'updateRole';
    }
}
