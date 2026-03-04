<?php

declare(strict_types=1);

namespace Modules\Metro\Policies;

use Modules\Metro\Models\MetroLine;
use Modules\Users\Models\User;

final class MetroLinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.metro.read");
    }

    public function view(User $user, MetroLine $model): bool
    {
        return $user->hasPermission("admin.metro.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.metro.create");
    }

    public function update(User $user, MetroLine $model): bool
    {
        return $user->hasPermission("admin.metro.update");
    }

    public function delete(User $user, MetroLine $model): bool
    {
        return $user->hasPermission("admin.metro.delete");
    }
}
