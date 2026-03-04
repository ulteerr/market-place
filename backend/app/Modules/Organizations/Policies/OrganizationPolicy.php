<?php

declare(strict_types=1);

namespace Modules\Organizations\Policies;

use Modules\Organizations\Models\Organization;
use Modules\Users\Models\User;

final class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("org.company.profile.read");
    }

    public function view(User $user, Organization $model): bool
    {
        return $user->hasPermission("org.company.profile.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("org.company.profile.update");
    }

    public function update(User $user, Organization $model): bool
    {
        return $user->hasPermission("org.company.profile.update");
    }

    public function delete(User $user, Organization $model): bool
    {
        return $user->hasPermission("org.company.profile.delete");
    }
}
