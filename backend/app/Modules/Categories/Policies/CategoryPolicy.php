<?php

declare(strict_types=1);

namespace Modules\Categories\Policies;

use Modules\Categories\Models\Category;
use Modules\Users\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.categories.read");
    }

    public function view(User $user, Category $model): bool
    {
        return $user->hasPermission("admin.categories.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.categories.create");
    }

    public function update(User $user, Category $model): bool
    {
        return $user->hasPermission("admin.categories.update");
    }

    public function delete(User $user, Category $model): bool
    {
        return $user->hasPermission("admin.categories.delete");
    }
}
