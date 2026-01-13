<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts;

use Modules\Users\Models\User;

interface TokenServiceInterface
{
    public function createToken(User $user): string;
}
