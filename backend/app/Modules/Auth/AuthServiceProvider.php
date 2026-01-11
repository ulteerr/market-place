<?php

declare(strict_types=1);

namespace Modules\Auth;

use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\PersonalAccessToken;

use App\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
	protected string $moduleName = 'Auth';

	public function boot(): void
	{
		parent::boot();
		Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
	}
}
