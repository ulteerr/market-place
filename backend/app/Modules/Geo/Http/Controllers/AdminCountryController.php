<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Controllers;

use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Geo\Http\Requests\CreateAdminCountryRequest;
use Modules\Geo\Http\Requests\UpdateAdminCountryRequest;
use Modules\Geo\Services\CountriesService;

final class AdminCountryController extends AdminCrudController
{
    public function __construct(private readonly CountriesService $countriesService) {}

    protected function service(): object
    {
        return $this->countriesService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminCountryRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminCountryRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return null;
    }

    protected function deleteMethod(): string
    {
        return "deleteById";
    }
}
