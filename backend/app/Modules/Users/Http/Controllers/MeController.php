<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Files\Services\FilesService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        private readonly FilesService $filesService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return UserResponseFactory::success($request->user());
    }

    public function updateProfile(UpdateMeProfileRequest $request): JsonResponse
    {
        $user = $this->usersService->updateUser($request->user(), $request->validated());

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

        $this->usersService->updateUser($request->user(), ["settings" => $mergedSettings]);

        return response()->noContent();
    }

    public function uploadAvatar(UploadMeAvatarRequest $request): JsonResponse
    {
        $file = $request->file("avatar");
        if (!$file instanceof UploadedFile) {
            abort(422, "Invalid avatar file");
        }

        $this->filesService->attachUploadedFile($file, $request->user(), "avatar");

        return UserResponseFactory::success($request->user()->fresh());
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $this->filesService->removeAttachedFile($request->user(), "avatar");

        return UserResponseFactory::success($request->user()->fresh());
    }

    public function streamSettings(Request $request): StreamedResponse
    {
        $user = $request->user();
        $maxDuration = app()->runningUnitTests() ? 1 : 25;

        return response()->stream(
            function () use ($user, $maxDuration): void {
                @ini_set("zlib.output_compression", "0");
                @ini_set("output_buffering", "off");

                echo "retry: 3000\n\n";

                $lastHash = md5(json_encode($user->settings ?? []));
                $stopAt = time() + $maxDuration;

                while (time() < $stopAt) {
                    if (connection_aborted()) {
                        break;
                    }

                    $user->refresh();
                    $settings = $user->settings ?? [];
                    $currentHash = md5(json_encode($settings));

                    if ($currentHash !== $lastHash) {
                        $payload = json_encode(
                            ["settings" => $settings],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        );

                        echo "event: settings\n";
                        echo "data: {$payload}\n\n";
                        $lastHash = $currentHash;
                    } else {
                        echo "event: ping\n";
                        echo "data: {}\n\n";
                    }

                    @ob_flush();
                    flush();
                    sleep(1);
                }
            },
            200,
            [
                "Content-Type" => "text/event-stream; charset=UTF-8",
                "Cache-Control" => "no-cache, no-store, must-revalidate",
                "Connection" => "keep-alive",
                "X-Accel-Buffering" => "no",
            ],
        );
    }
}
