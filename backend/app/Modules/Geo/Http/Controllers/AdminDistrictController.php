<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Geo\Http\Requests\CreateAdminDistrictRequest;
use Modules\Geo\Http\Requests\UpdateAdminDistrictRequest;
use Modules\Geo\Services\DistrictsService;

final class AdminDistrictController extends AdminCrudController
{
    public function __construct(private readonly DistrictsService $districtsService) {}

    protected function service(): object
    {
        return $this->districtsService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminDistrictRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminDistrictRequest::class;
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
        $cityId = trim((string) request()->query("city_id", ""));

        return $cityId === "" ? [] : ["city_id" => $cityId];
    }
}
