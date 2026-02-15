<?php

declare(strict_types=1);

namespace Modules\Users\Policies;

use Modules\Users\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.users.read");
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission("admin.users.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.users.create");
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission("admin.users.update");
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission("admin.users.delete");
    }
}
