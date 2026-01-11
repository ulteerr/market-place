<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegistrationRequest;
use Modules\Auth\Services\AuthService;
use Modules\Users\Services\UsersService;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
	public function __construct(
		private readonly AuthService $authService,
		private readonly UsersService $usersService
	) {}

	public function login(LoginRequest $request)
	{
		$user = $this->authService->login(
			$request->validated()
		);

		return response()->json([
			'status' => 'ok',
			'user'   => $user,
		]);
	}

	public function register(RegistrationRequest $request)
	{
		$user = $this->usersService->createUser(
			$request->validated()
		);

		return response()->json([
			'status' => 'ok',
			'user'   => $user,
		]);
	}

	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();

		return response()->json([
			'status' => 'ok',
			'message' => 'Logged out successfully'
		]);
	}
}
