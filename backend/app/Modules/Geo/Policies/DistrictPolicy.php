<?php

declare(strict_types=1);

namespace Modules\Geo\Policies;

use Modules\Geo\Models\District;
use Modules\Users\Models\User;

final class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.geo.read");
    }

    public function view(User $user, District $model): bool
    {
        return $user->hasPermission("admin.geo.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.geo.create");
    }

    public function update(User $user, District $model): bool
    {
        return $user->hasPermission("admin.geo.update");
    }

    public function delete(User $user, District $model): bool
    {
        return $user->hasPermission("admin.geo.delete");
    }
}
