<?php

declare(strict_types=1);

namespace Modules\Geo\Policies;

use Modules\Geo\Models\City;
use Modules\Users\Models\User;

final class CityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.geo.read");
    }

    public function view(User $user, City $model): bool
    {
        return $user->hasPermission("admin.geo.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.geo.create");
    }

    public function update(User $user, City $model): bool
    {
        return $user->hasPermission("admin.geo.update");
    }

    public function delete(User $user, City $model): bool
    {
        return $user->hasPermission("admin.geo.delete");
    }
}
