<?php

declare(strict_types=1);

namespace Modules\Users\Contracts;

use Modules\Users\Models\User;
use Illuminate\Support\Collection;

interface UsersServiceInterface
{
    public function createUser(array $data): User;

    public function updateUser(User $user, array $data): User;

    public function findByEmail(string $email): ?User;

    public function findByEmailOrPhone(string $value): ?User;

    public function getUserById(string $id): ?User;

    public function getChildrenForParent(string $parentId): Collection;
}
