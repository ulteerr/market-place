<?php
declare(strict_types=1);

namespace Modules\Auth\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;
use App\Shared\Traits\HasUuid;

final class PersonalAccessToken extends SanctumToken
{
    use HasUuid;
}
