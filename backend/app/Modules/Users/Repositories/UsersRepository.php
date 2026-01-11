<?php
declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\User;
use Illuminate\Support\Facades\Hash;

final class UsersRepository implements UsersRepositoryInterface
{
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByEmailOrPhone(string $value): ?User
    {
        return User::where('email', $value)
                   ->orWhere('phone', $value)
                   ->first();
    }

    public function findById(string $id): ?User
    {
        return User::with('children')->find($id);
    }
}
