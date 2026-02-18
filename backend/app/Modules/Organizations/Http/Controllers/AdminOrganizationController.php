<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Organizations\Http\Requests\CreateAdminOrganizationRequest;
use Modules\Organizations\Http\Requests\UpdateAdminOrganizationRequest;
use Modules\Organizations\Services\OrganizationsService;

final class AdminOrganizationController extends AdminCrudController
{
    public function __construct(private readonly OrganizationsService $organizationsService) {}

    protected function service(): object
    {
        return $this->organizationsService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminOrganizationRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminOrganizationRequest::class;
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
