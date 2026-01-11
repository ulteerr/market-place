<?php
declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\User;

interface UsersRepositoryInterface
{
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function findByEmail(string $email): ?User;
    public function findByEmailOrPhone(string $value): ?User;
    public function findById(string $id): ?User;
}
