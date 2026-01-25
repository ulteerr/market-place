<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegistrationRequest;
use Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Users\Http\Responses\UserResponseFactory;

final class AuthController extends Controller
{
	public function __construct(
		private readonly AuthService $authService,
	) {}

	public function login(LoginRequest $request): JsonResponse
	{
		$result = $this->authService->login(
			$request->validated()
		);

		return UserResponseFactory::success(
			$result['user'],
			$result['token']
		);
	}

	public function register(RegistrationRequest $request): JsonResponse
	{
		$result = $this->authService->register(
			$request->validated()
		);

		return UserResponseFactory::success(
			$result['user'],
			$result['token'],
			201
		);
	}

	public function logout(Request $request): JsonResponse
	{
		$request->user()->currentAccessToken()->delete();

		return StatusResponseFactory::ok('Logged out successfully');
	}
}
