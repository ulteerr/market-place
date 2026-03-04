<?php

declare(strict_types=1);

namespace Modules\Auth;

use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\PersonalAccessToken;
use App\Support\ModuleServiceProvider;
use Modules\ActionLog\Models\ActionLog;
use Modules\ActionLog\Policies\ActionLogPolicy;
use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Auth\Services\SanctumTokenService;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\ChangeLog\Policies\ChangeLogPolicy;
use Modules\Children\Models\Child;
use Modules\Children\Policies\ChildPolicy;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;
use Modules\Geo\Policies\CityPolicy;
use Modules\Geo\Policies\CountryPolicy;
use Modules\Geo\Policies\DistrictPolicy;
use Modules\Geo\Policies\RegionPolicy;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Models\MetroStation;
use Modules\Metro\Policies\MetroLinePolicy;
use Modules\Metro\Policies\MetroStationPolicy;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Policies\OrganizationPolicy;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Users\Policies\RolePolicy;
use Modules\Users\Policies\UserPolicy;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Auth";

    public function boot(): void
    {
        parent::boot();
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        $this->registerPolicies();
    }

    public function register(): void
    {
        $this->app->bind(TokenServiceInterface::class, SanctumTokenService::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(ActionLog::class, ActionLogPolicy::class);
        Gate::policy(ChangeLog::class, ChangeLogPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Child::class, ChildPolicy::class);
        Gate::policy(Country::class, CountryPolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(City::class, CityPolicy::class);
        Gate::policy(District::class, DistrictPolicy::class);
        Gate::policy(MetroLine::class, MetroLinePolicy::class);
        Gate::policy(MetroStation::class, MetroStationPolicy::class);
    }
}
