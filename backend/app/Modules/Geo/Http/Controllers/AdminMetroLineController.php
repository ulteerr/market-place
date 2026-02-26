<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Geo\Http\Requests\CreateAdminMetroLineRequest;
use Modules\Geo\Http\Requests\UpdateAdminMetroLineRequest;
use Modules\Geo\Services\MetroLinesService;

final class AdminMetroLineController extends AdminCrudController
{
    public function __construct(private readonly MetroLinesService $metroLinesService) {}

    protected function service(): object
    {
        return $this->metroLinesService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminMetroLineRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminMetroLineRequest::class;
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
