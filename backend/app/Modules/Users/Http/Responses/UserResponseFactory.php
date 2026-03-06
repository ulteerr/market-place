<?php

declare(strict_types=1);

namespace Modules\Users\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Models\User;
use Modules\Users\Services\PresenceService;

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
            "user" => self::presentUser($user),
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

        $visibilityById = [];
        $presenceUsers = [];
        foreach ($collection as $item) {
            if (!$item instanceof User) {
                continue;
            }

            $canViewPresence = self::canCurrentUserViewPresence($item);
            $visibilityById[(string) $item->id] = $canViewPresence;

            if ($canViewPresence) {
                $presenceUsers[] = $item;
            }
        }

        $onlineMap = self::presence()->isOnlineMap($presenceUsers);

        $rows = $collection->map(function (User $item) use ($visibilityById, $onlineMap): array {
            $id = (string) $item->id;
            $canViewPresence = $visibilityById[$id] ?? false;

            return (new UserResource(
                $item,
                $canViewPresence,
                $canViewPresence ? $onlineMap[$id] ?? false : false,
            ))->resolve();
        });

        return StatusResponseFactory::paginated($users, $rows->all(), $status);
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
            self::presentUser($user)->resolve(),
            $status,
        );
    }

    private static function presentUser(User $user): UserResource
    {
        $canCurrentUserViewPresence = self::canCurrentUserViewPresence($user);

        return new UserResource(
            $user,
            $canCurrentUserViewPresence,
            $canCurrentUserViewPresence ? self::presence()->isOnline($user) : false,
        );
    }

    private static function canCurrentUserViewPresence(User $user): bool
    {
        $viewer = auth()->user();
        if (!$viewer instanceof User) {
            return false;
        }

        return Gate::forUser($viewer)->allows("viewLastSeen", $user);
    }

    private static function presence(): PresenceService
    {
        return app(PresenceService::class);
    }
}
