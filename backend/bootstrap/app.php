<?php

use App\Shared\Http\Middleware\CanAccessAdminPanel;
use App\Shared\Http\Middleware\CanPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            "can_access_admin_panel" => CanAccessAdminPanel::class,
            "can_permission" => CanPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is("api/*")) {
                return response()->json(
                    [
                        "message" => "Validation error",
                        "errors" => $e->errors(),
                    ],
                    422,
                );
            }

            return null;
        });
    })
    ->create();
