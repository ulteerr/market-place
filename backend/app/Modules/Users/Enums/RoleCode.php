<?php

declare(strict_types=1);

namespace Modules\Users\Enums;

enum RoleCode: string
{
    case PARTICIPANT = "participant";
    case SUPER_ADMIN = "super_admin";
    case ADMIN = "admin";
    case MODERATOR = "moderator";
}
