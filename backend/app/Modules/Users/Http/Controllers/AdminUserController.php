<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Users\Models\User;
use Modules\Users\Http\Responses\UserResponseFactory;
use Modules\Users\Http\Requests\CreateAdminUserRequest;
use Modules\Users\Http\Requests\UpdateAdminUserRequest;
use Modules\Users\Services\PresenceService;
use Modules\Users\Services\UsersService;

final class AdminUserController extends AdminCrudController
{
    public function __construct(
        private readonly UsersService $usersService,
        private readonly PresenceService $presenceService,
    ) {}

    protected function service(): object
    {
        return $this->usersService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminUserRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminUserRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return UserResponseFactory::class;
    }

    protected function createMethod(): string
    {
        return "createUser";
    }

    protected function findMethod(): string
    {
        return "getUserById";
    }

    protected function updateMethod(): string
    {
        return "updateUser";
    }

    protected function deleteMethod(): string
    {
        return "deleteUser";
    }

    protected function policyModelClass(): ?string
    {
        return User::class;
    }

    protected function updateArguments(string $id, array $data): array
    {
        $user = $this->usersService->getUserById($id);

        if (!$user) {
            abort(404, "Not found");
        }

        return [$user, $data];
    }

    protected function indexFilters(): array
    {
        $accessGroup = trim((string) request()->query("access_group", ""));

        if (!in_array($accessGroup, ["admin", "basic"], true)) {
            return [];
        }

        return [
            "access_group" => $accessGroup,
        ];
    }

    public function stats(): JsonResponse
    {
        $users = User::query()
            ->select(["id", "last_seen_at"])
            ->get();

        $onlineMap = $this->presenceService->isOnlineMap($users);
        $onlineUsers = array_reduce(
            $onlineMap,
            static fn(int $sum, bool $isOnline): int => $sum + ($isOnline ? 1 : 0),
            0,
        );

        return StatusResponseFactory::success([
            "total_users" => $users->count(),
            "online_users" => $onlineUsers,
            "updated_at" => now()->toIso8601String(),
        ]);
    }
}
