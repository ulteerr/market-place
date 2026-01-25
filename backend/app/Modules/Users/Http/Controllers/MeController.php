<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Users\Http\Requests\UpdateMeProfileRequest;
use Modules\Users\Http\Requests\UpdateMePasswordRequest;
use Modules\Users\Http\Responses\UserResponseFactory;
use Modules\Users\Services\UsersService;

final class MeController extends Controller
{
    public function __construct(
        private readonly UsersService $usersService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return UserResponseFactory::success($request->user());
    }

    public function updateProfile(UpdateMeProfileRequest $request): JsonResponse
    {
        $user = $this->usersService->updateUser(
            $request->user(),
            $request->validated()
        );

        return UserResponseFactory::success($user);
    }

    public function updatePassword(UpdateMePasswordRequest $request): JsonResponse
    {
        $user = $this->usersService->updateUser(
            $request->user(),
            $request->validated()
        );

        return UserResponseFactory::success($user);
    }
}
