<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Metro\Http\Requests\CreateAdminMetroStationRequest;
use Modules\Metro\Http\Requests\UpdateAdminMetroStationRequest;
use Modules\Metro\Services\MetroStationsService;

final class AdminMetroStationController extends AdminCrudController
{
    public function __construct(private readonly MetroStationsService $metroStationsService) {}

    protected function service(): object
    {
        return $this->metroStationsService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminMetroStationRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminMetroStationRequest::class;
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
        $lineId = trim((string) request()->query("metro_line_id", ""));
        $filters = [];

        if ($cityId !== "") {
            $filters["city_id"] = $cityId;
        }
        if ($lineId !== "") {
            $filters["metro_line_id"] = $lineId;
        }

        return $filters;
    }
}
