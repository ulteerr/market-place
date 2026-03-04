<?php

declare(strict_types=1);

namespace Modules\Children\Policies;

use Modules\Children\Models\Child;
use Modules\Users\Models\User;

final class ChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("org.children.read");
    }

    public function view(User $user, Child $model): bool
    {
        return $user->hasPermission("org.children.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("org.children.write");
    }

    public function update(User $user, Child $model): bool
    {
        return $user->hasPermission("org.children.write");
    }

    public function delete(User $user, Child $model): bool
    {
        return $user->hasPermission("org.children.write");
    }
}
