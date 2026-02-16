<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Policies;

use Modules\ChangeLog\Models\ChangeLog;
use Modules\Children\Models\Child;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

final class ChangeLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.changelog.read");
    }

    public function view(User $user, ChangeLog $model): bool
    {
        return $user->hasPermission("admin.changelog.read");
    }

    public function rollback(User $user, ChangeLog $model): bool
    {
        if (!$user->hasPermission("admin.changelog.rollback")) {
            return false;
        }

        return match ($model->auditable_type) {
            User::class => $user->hasPermission("admin.users.update"),
            Role::class => $user->hasPermission("admin.roles.update"),
            Child::class => $user->hasPermission("org.children.write"),
            default => false,
        };
    }
}
