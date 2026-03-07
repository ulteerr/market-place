<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName;
    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadChannels();
        $this->loadMigrations();
        $this->loadPolicies();
    }

    protected function loadRoutes(): void
    {
        $routesPath = app_path("Modules/{$this->moduleName}/routes.php");

        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }

    protected function loadMigrations(): void
    {
        $migrationsPath = app_path("Modules/{$this->moduleName}/Database/Migrations");

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function loadChannels(): void
    {
        $channelsPath = app_path("Modules/{$this->moduleName}/channels.php");

        if (!file_exists($channelsPath)) {
            return;
        }

        if (!Route::has("broadcasting.auth")) {
            Broadcast::routes(["middleware" => ["auth:sanctum"]]);
        }

        require $channelsPath;
    }

    protected function loadPolicies(): void
    {
        // будем подключать позже
    }
}
