<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Users\Models\User;

final class SanctumTokenService implements TokenServiceInterface
{
    public function createToken(User $user): string
    {
        return $user->createToken('api-token')->plainTextToken;
    }
}
