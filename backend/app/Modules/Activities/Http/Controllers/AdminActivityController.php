<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Shared\Http\Controllers\AdminCrudController;
use Modules\Activities\Http\Requests\CreateAdminActivityRequest;
use Modules\Activities\Http\Requests\UpdateAdminActivityRequest;
use Modules\Activities\Http\Responses\ActivityResponseFactory;
use Modules\Activities\Models\Activity;
use Modules\Activities\Services\ActivitiesService;

final class AdminActivityController extends AdminCrudController
{
    public function __construct(private readonly ActivitiesService $activitiesService) {}

    protected function service(): object
    {
        return $this->activitiesService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminActivityRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminActivityRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return ActivityResponseFactory::class;
    }

    protected function createMethod(): string
    {
        return "create";
    }

    protected function findMethod(): string
    {
        return "findById";
    }

    protected function updateMethod(): string
    {
        return "update";
    }

    protected function policyModelClass(): ?string
    {
        return Activity::class;
    }

    public function slugPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255"],
            "ignore_id" => ["nullable", "uuid", "exists:activities,id"],
        ]);

        $slug = $this->activitiesService->previewSlug(
            (string) $validated["name"],
            isset($validated["ignore_id"]) ? (string) $validated["ignore_id"] : null,
        );

        return StatusResponseFactory::success([
            "slug" => $slug,
        ]);
    }
}
