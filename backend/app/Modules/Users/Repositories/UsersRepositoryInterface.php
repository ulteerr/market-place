<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


interface UsersRepositoryInterface
{
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function findByEmail(string $email): ?User;
    public function findByEmailOrPhone(string $value): ?User;
    public function findById(string $id): ?User;
    public function paginate(int $perPage = 20): LengthAwarePaginator;
    public function delete(User $user): void;
}
