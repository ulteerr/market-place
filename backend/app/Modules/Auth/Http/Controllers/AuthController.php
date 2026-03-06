<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Services\ObservabilityService;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegistrationRequest;
use Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Modules\Users\Http\Responses\UserResponseFactory;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ObservabilityService $observabilityService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $startedAt = microtime(true);
        try {
            $result = $this->authService->login($request->validated());

            $this->observabilityService->recordEvent(
                "auth",
                "auth.controller",
                "login",
                "ok",
                "info",
                (int) ((microtime(true) - $startedAt) * 1000),
                ["user_id" => (string) $result["user"]->id],
            );

            return UserResponseFactory::success($result["user"], $result["token"]);
        } catch (Throwable $exception) {
            $this->observabilityService->recordEvent(
                "auth",
                "auth.controller",
                "login",
                "error",
                "warning",
                (int) ((microtime(true) - $startedAt) * 1000),
                ["error" => $exception->getMessage()],
            );

            throw $exception;
        }
    }

    public function register(RegistrationRequest $request): JsonResponse
    {
        $startedAt = microtime(true);
        try {
            $result = $this->authService->register($request->validated());

            $this->observabilityService->recordEvent(
                "auth",
                "auth.controller",
                "register",
                "ok",
                "info",
                (int) ((microtime(true) - $startedAt) * 1000),
                ["user_id" => (string) $result["user"]->id],
            );

            return UserResponseFactory::success($result["user"], $result["token"], 201);
        } catch (Throwable $exception) {
            $this->observabilityService->recordEvent(
                "auth",
                "auth.controller",
                "register",
                "error",
                "warning",
                (int) ((microtime(true) - $startedAt) * 1000),
                ["error" => $exception->getMessage()],
            );

            throw $exception;
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $this->authService->logout($request->user());
        $request->user()->currentAccessToken()->delete();
        $this->observabilityService->recordEvent(
            "auth",
            "auth.controller",
            "logout",
            "ok",
            "info",
            (int) ((microtime(true) - $startedAt) * 1000),
            ["user_id" => (string) $request->user()?->id],
        );

        return StatusResponseFactory::ok("Logged out successfully");
    }
}
