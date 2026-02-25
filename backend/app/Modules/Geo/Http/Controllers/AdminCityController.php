<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Geo\Http\Requests\CreateAdminCityRequest;
use Modules\Geo\Http\Requests\UpdateAdminCityRequest;
use Modules\Geo\Services\CitiesService;

final class AdminCityController extends AdminCrudController
{
    public function __construct(private readonly CitiesService $citiesService) {}

    protected function service(): object
    {
        return $this->citiesService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminCityRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminCityRequest::class;
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
        $regionId = trim((string) request()->query("region_id", ""));
        $filters = [];

        if ($countryId !== "") {
            $filters["country_id"] = $countryId;
        }
        if ($regionId !== "") {
            $filters["region_id"] = $regionId;
        }

        return $filters;
    }
}
