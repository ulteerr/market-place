<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegistrationRequest;
use Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
	public function __construct(
		private readonly AuthService $authService,
	) {}

	public function login(LoginRequest $request)
	{
		$result = $this->authService->login(
			$request->validated()
		);

		return response()->json([
			'status' => 'ok',
			'user'   => $result['user'],
			'token'  => $result['token'],
		]);
	}

	public function register(RegistrationRequest $request)
	{
		$result = $this->authService->register(
			$request->validated()
		);

		return response()->json([
			'status' => 'ok',
			'user'   => $result['user'],
			'token'  => $result['token'],
		], 201);
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
