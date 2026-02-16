<?php

declare(strict_types=1);

namespace Modules\Children\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Children\Http\Requests\CreateAdminChildRequest;
use Modules\Children\Http\Requests\UpdateAdminChildRequest;
use Modules\Children\Services\ChildService;

final class AdminChildController extends AdminCrudController
{
    public function __construct(private readonly ChildService $childService) {}

    protected function service(): object
    {
        return $this->childService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminChildRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminChildRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return null;
    }

    protected function createMethod(): string
    {
        return "create";
    }

    protected function findMethod(): string
    {
        return "findById";
    }

    protected function updateMethod(): string
    {
        return "update";
    }

    protected function deleteMethod(): string
    {
        return "deleteById";
    }
}
