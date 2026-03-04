<?php

declare(strict_types=1);

namespace Modules\Geo\Policies;

use Modules\Geo\Models\Country;
use Modules\Users\Models\User;

final class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.geo.read");
    }

    public function view(User $user, Country $model): bool
    {
        return $user->hasPermission("admin.geo.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("admin.geo.create");
    }

    public function update(User $user, Country $model): bool
    {
        return $user->hasPermission("admin.geo.update");
    }

    public function delete(User $user, Country $model): bool
    {
        return $user->hasPermission("admin.geo.delete");
    }
}
