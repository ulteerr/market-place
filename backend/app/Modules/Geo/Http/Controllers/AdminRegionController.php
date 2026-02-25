<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Geo\Http\Requests\CreateAdminRegionRequest;
use Modules\Geo\Http\Requests\UpdateAdminRegionRequest;
use Modules\Geo\Services\RegionsService;

final class AdminRegionController extends AdminCrudController
{
    public function __construct(private readonly RegionsService $regionsService) {}

    protected function service(): object
    {
        return $this->regionsService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminRegionRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminRegionRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return null;
    }

    protected function deleteMethod(): string
    {
        return "deleteById";
    }

    protected function indexFilters(): array
    {
        $countryId = trim((string) request()->query("country_id", ""));

        return $countryId === "" ? [] : ["country_id" => $countryId];
    }
}
