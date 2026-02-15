<?php

declare(strict_types=1);

namespace Modules\Users\Policies;

use Modules\Users\Models\Role;
use Modules\Users\Models\User;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.roles.read");
    }

    public function view(User $user, Role $model): bool
    {
        return $user->hasPermission("admin.roles.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.roles.create");
    }

    public function update(User $user, Role $model): bool
    {
        return $user->hasPermission("admin.roles.update");
    }

    public function delete(User $user, Role $model): bool
    {
        return $user->hasPermission("admin.roles.delete");
    }
}
