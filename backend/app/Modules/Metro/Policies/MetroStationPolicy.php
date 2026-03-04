<?php

declare(strict_types=1);

namespace Modules\Metro\Policies;

use Modules\Metro\Models\MetroStation;
use Modules\Users\Models\User;

final class MetroStationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.metro.read");
    }

    public function view(User $user, MetroStation $model): bool
    {
        return $user->hasPermission("admin.metro.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.metro.create");
    }

    public function update(User $user, MetroStation $model): bool
    {
        return $user->hasPermission("admin.metro.update");
    }

    public function delete(User $user, MetroStation $model): bool
    {
        return $user->hasPermission("admin.metro.delete");
    }
}
