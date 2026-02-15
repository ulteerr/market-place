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
    }
}
