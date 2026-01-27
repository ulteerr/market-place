<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


final class UsersRepository implements UsersRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
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
        return User::find($id);
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return User::paginate($perPage);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
