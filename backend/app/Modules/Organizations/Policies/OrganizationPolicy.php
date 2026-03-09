<?php

declare(strict_types=1);

namespace Modules\Organizations\Policies;

use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationUser;
use Modules\Users\Models\User;

final class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("org.company.profile.read");
    }

    public function view(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.company.profile.read");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("org.company.profile.update");
    }

    public function update(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.company.profile.update");
    }

    public function delete(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.company.profile.delete");
    }

    public function viewMembers(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.members.read");
    }

    public function manageMembers(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.members.write");
    }

    public function viewClients(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.children.read");
    }

    public function manageClients(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.children.write");
    }

    public function viewJoinRequests(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.members.read");
    }

    public function reviewJoinRequests(User $user, Organization $model): bool
    {
        return $this->canAccessOrganization($user, $model, "org.members.write");
    }

    private function canAccessOrganization(
        User $user,
        Organization $organization,
        string $permissionCode,
    ): bool {
        if (!$user->hasPermission($permissionCode)) {
            return false;
        }

        if ($user->hasPermission("admin.panel.access")) {
            return true;
        }

        if ((string) $organization->owner_user_id === (string) $user->id) {
            return true;
        }

        return OrganizationUser::query()
            ->where("organization_id", (string) $organization->id)
            ->where("user_id", (string) $user->id)
            ->where("status", "active")
            ->exists();
    }
}
