<?php
declare(strict_types=1);

namespace App\Support\Testing;

use Modules\Users\Models\User;

trait InteractsWithAuth
{
    protected function actingAsUser(?User $user = null): array
    {
        $user ??= User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ];
    }
}
