<?php

declare(strict_types=1);

namespace Modules\ActionLog\Policies;

use Modules\ActionLog\Models\ActionLog;
use Modules\Users\Models\User;

final class ActionLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("admin.action-log.read");
    }

    public function view(User $user, ActionLog $model): bool
    {
        return $user->hasPermission("admin.action-log.read");
    }
}
