<?php

declare(strict_types=1);

namespace Modules\Users\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public static function paginated(LengthAwarePaginator $users, int $status = 200): JsonResponse
    {
        $collection = $users->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing([
                "roles:id,code",
                "avatar:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
            ]);
        }

        return StatusResponseFactory::paginated(
            $users,
            UserResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        User $user,
        int $status = 200,
    ): JsonResponse {
        $user->loadMissing(["roles", "avatar"]);
        $user->loadMissing(["permissionOverrides.permission"]);

        return StatusResponseFactory::successWithMessage(
            $message,
            (new UserResource($user))->resolve(),
            $status,
        );
    }
}
