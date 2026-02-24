<?php

use App\Shared\Http\Middleware\CanAccessAdminPanel;
use App\Shared\Http\Middleware\CanPermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is("api/*")) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Unauthenticated.",
                        "errors" => null,
                    ],
                    401,
                );
            }

            return null;
        });

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

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is("api/*")) {
                return null;
            }

            $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            if ($statusCode < 500) {
                return null;
            }

            $response = [
                "status" => "error",
                "message" => "Server error",
                "errors" => null,
            ];

            if (config("app.debug")) {
                $response["debug"] = $e->getMessage();
            }

            return response()->json($response, $statusCode);
        });
    })
    ->create();
