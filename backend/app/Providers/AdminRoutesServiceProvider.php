<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AdminRoutesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::macro('adminCrud', function (string $uri, string $controller) {
            Route::get($uri, [$controller, 'index']);
            Route::post($uri, [$controller, 'store']);
            Route::get("$uri/{id}", [$controller, 'show']);
            Route::patch("$uri/{id}", [$controller, 'update']);
            Route::delete("$uri/{id}", [$controller, 'destroy']);
        });
    }
}
