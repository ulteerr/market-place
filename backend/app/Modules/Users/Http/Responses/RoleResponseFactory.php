<?php

declare(strict_types=1);

namespace Modules\Users\Http\Responses;

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
}
