<?php

declare(strict_types=1);

namespace Modules\Users\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Users\Http\Resources\RoleResource;
use Modules\Users\Models\Role;

final class RoleResponseFactory
{
    public static function success(Role $role, int $status = 200): JsonResponse
    {
        $role->loadMissing(["permissions"]);

        $payload = [
            "status" => "ok",
            "role" => new RoleResource($role),
        ];
        return response()->json($payload, $status);
    }

    public static function paginated(LengthAwarePaginator $roles, int $status = 200): JsonResponse
    {
        $collection = $roles->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing(["permissions"]);
        }

        return StatusResponseFactory::paginated(
            $roles,
            RoleResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Role $role,
        int $status = 200,
    ): JsonResponse {
        $role->loadMissing(["permissions"]);

        return StatusResponseFactory::successWithMessage(
            $message,
            (new RoleResource($role))->resolve(),
            $status,
        );
    }
}
