<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ChangeLog\Services\ChangeLogContext;
use Modules\Users\Events\MeSettingsUpdated;
use Symfony\Component\HttpFoundation\Response;
use Modules\Users\Http\Requests\UploadMeAvatarRequest;
use Modules\Users\Http\Requests\UpdateMeProfileRequest;
use Modules\Users\Http\Requests\UpdateMePasswordRequest;
use Modules\Users\Http\Requests\UpdateMeSettingsRequest;
use Modules\Users\Http\Responses\UserResponseFactory;
use Modules\Users\Services\UsersService;

final class MeController extends Controller
{
    public function __construct(
        private readonly UsersService $usersService,
        private readonly ChangeLogContext $changeLogContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return UserResponseFactory::success($request->user());
    }

    public function updateProfile(UpdateMeProfileRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = $this->changeLogContext->withMeta(
            ["scope" => "profile"],
            fn() => $this->usersService->updateUser($request->user(), $payload),
        );

        return UserResponseFactory::success($user);
    }

    public function updatePassword(UpdateMePasswordRequest $request): JsonResponse
    {
        $user = $this->usersService->updateUser($request->user(), $request->validated());

        return UserResponseFactory::success($user);
    }

    public function updateSettings(UpdateMeSettingsRequest $request): Response
    {
        $incomingSettings = $request->validated("settings");
        $currentSettings = $request->user()->settings ?? [];

        $mergedSettings = [...$currentSettings, ...$incomingSettings];

        if (array_key_exists("admin_crud_preferences", $incomingSettings)) {
            $mergedSettings["admin_crud_preferences"] = [
                ...is_array($currentSettings["admin_crud_preferences"] ?? null)
                    ? $currentSettings["admin_crud_preferences"]
                    : [],
                ...is_array($incomingSettings["admin_crud_preferences"] ?? null)
                    ? $incomingSettings["admin_crud_preferences"]
                    : [],
            ];
        }

        if (array_key_exists("admin_navigation_sections", $incomingSettings)) {
            $mergedSettings["admin_navigation_sections"] = [
                ...is_array($currentSettings["admin_navigation_sections"] ?? null)
                    ? $currentSettings["admin_navigation_sections"]
                    : [],
                ...is_array($incomingSettings["admin_navigation_sections"] ?? null)
                    ? $incomingSettings["admin_navigation_sections"]
                    : [],
            ];
        }

        $user = $this->usersService->updateUser($request->user(), ["settings" => $mergedSettings]);
        event(new MeSettingsUpdated($user));

        return response()->noContent();
    }

    public function uploadAvatar(UploadMeAvatarRequest $request): JsonResponse
    {
        $file = $request->file("avatar");
        if (!$file instanceof UploadedFile) {
            abort(422, "Invalid avatar file");
        }

        $user = $this->changeLogContext->withMeta(
            ["scope" => "profile"],
            fn() => $this->usersService->updateUser($request->user(), ["avatar" => $file]),
        );

        return UserResponseFactory::success($user->fresh());
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $this->changeLogContext->withMeta(
            ["scope" => "profile"],
            fn() => $this->usersService->updateUser($request->user(), ["avatar_delete" => true]),
        );

        return UserResponseFactory::success($user->fresh());
    }
}
