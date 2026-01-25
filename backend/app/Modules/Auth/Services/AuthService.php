<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Models\User;

final class AuthService
{
    public function __construct(
        private readonly UsersServiceInterface $usersService,
        private readonly TokenServiceInterface $tokenService
    ) {}

    public function login(array $credentials): array
    {
        $user = $this->usersService->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        return $this->buildAuthResponse($user);
    }

    public function register(array $data): array
    {
        $user = $this->usersService->createUser($data);
        return $this->buildAuthResponse($user);
    }

    private function buildAuthResponse(User $user): array
    {
        return [
            'user'  => $user,
            'token' => $this->tokenService->createToken($user),
        ];
    }
}
