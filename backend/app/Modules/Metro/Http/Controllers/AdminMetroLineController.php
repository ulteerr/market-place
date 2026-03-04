<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Metro\Http\Requests\CreateAdminMetroLineRequest;
use Modules\Metro\Http\Requests\UpdateAdminMetroLineRequest;
use Modules\Metro\Http\Responses\MetroLineResponseFactory;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Services\MetroLinesService;

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
        return MetroLineResponseFactory::class;
    }

    protected function indexFilters(): array
    {
        $cityId = trim((string) request()->query("city_id", ""));

        return $cityId === "" ? [] : ["city_id" => $cityId];
    }

    protected function policyModelClass(): ?string
    {
        return MetroLine::class;
    }
}
