<?php

declare(strict_types=1);

namespace Modules\Geo;

use App\Support\ModuleServiceProvider;
use Modules\Geo\Repositories\CitiesRepository;
use Modules\Geo\Repositories\CitiesRepositoryInterface;
use Modules\Geo\Repositories\CountriesRepository;
use Modules\Geo\Repositories\CountriesRepositoryInterface;
use Modules\Geo\Repositories\DistrictsRepository;
use Modules\Geo\Repositories\DistrictsRepositoryInterface;
use Modules\Geo\Repositories\RegionsRepository;
use Modules\Geo\Repositories\RegionsRepositoryInterface;

final class GeoServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Geo";

    public function register(): void
    {
        $this->app->bind(CountriesRepositoryInterface::class, CountriesRepository::class);
        $this->app->bind(RegionsRepositoryInterface::class, RegionsRepository::class);
        $this->app->bind(CitiesRepositoryInterface::class, CitiesRepository::class);
        $this->app->bind(DistrictsRepositoryInterface::class, DistrictsRepository::class);
    }
}
