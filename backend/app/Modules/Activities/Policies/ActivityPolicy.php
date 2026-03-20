<?php

declare(strict_types=1);

namespace Modules\Activities\Policies;

use Modules\Activities\Models\Activity;
use Modules\Users\Models\User;

final class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.activities.read");
    }

    public function view(User $user, Activity $model): bool
    {
        return $user->hasPermission("admin.activities.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.activities.create");
    }

    public function update(User $user, Activity $model): bool
    {
        return $user->hasPermission("admin.activities.update");
    }

    public function delete(User $user, Activity $model): bool
    {
        return $user->hasPermission("admin.activities.delete");
    }
}
