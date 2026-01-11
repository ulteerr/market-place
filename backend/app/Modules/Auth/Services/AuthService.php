<?php
declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Users\Services\UsersService;
use Modules\Users\Models\User;

final class AuthService
{
    public function __construct(
        private readonly UsersService $usersService
    ) {}

    public function login(array $credentials): array
    {
        $user = $this->usersService->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        return $this->formatUserWithToken($user);
    }

    public function register(array $data): array
    {
        $user = $this->usersService->createUser($data);
        return $this->formatUserWithToken($user);
    }

    private function formatUserWithToken(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'id'         => $user->id,
            'email'      => $user->email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'token'      => $token,
        ];
    }
}
