<?php

declare(strict_types=1);

namespace Modules\Users\Http\Responses;

use Illuminate\Http\JsonResponse;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Models\User;

final class UserResponseFactory
{
    public static function success(
        User $user,
        ?string $token = null,
        int $status = 200,
    ): JsonResponse {
        $user->loadMissing(["roles", "avatar"]);
        $user->loadMissing(["permissionOverrides.permission"]);

        $payload = [
            "status" => "ok",
            "user" => new UserResource($user),
        ];

        if ($token !== null) {
            $payload["token"] = $token;
        }

        return response()->json($payload, $status);
    }
}
