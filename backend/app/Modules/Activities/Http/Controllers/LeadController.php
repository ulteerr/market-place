<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Activities\Http\Requests\CreateLeadRequest;
use Modules\Activities\Http\Requests\UpdateLeadStatusRequest;
use Modules\Activities\Http\Resources\LeadResource;
use Modules\Activities\Services\LeadsService;
use Modules\Users\Models\User;

final class LeadController
{
    public function __construct(private readonly LeadsService $leadsService) {}

    public function submit(CreateLeadRequest $request, string $activityId): JsonResponse
    {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $lead = $this->leadsService->submit($activityId, $actor, $request->validated());

        return StatusResponseFactory::successWithMessage(
            "Lead submitted",
            new LeadResource($lead),
            201,
        );
    }

    public function organizationIndex(Request $request, string $organizationId): JsonResponse
    {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $perPage = max(1, min(100, (int) $request->integer("per_page", 20)));
        $items = $this->leadsService->listForOrganization($organizationId, $actor, $perPage, [
            "status" => (string) $request->query("status", ""),
            "request_for_type" => (string) $request->query("request_for_type", ""),
            "activity_id" => (string) $request->query("activity_id", ""),
            "search" => (string) $request->query("search", ""),
            "sort_by" => (string) $request->query("sort_by", "created_at"),
            "sort_dir" => (string) $request->query("sort_dir", "desc"),
        ]);

        return StatusResponseFactory::success($this->transformPaginator($items));
    }

    public function organizationUpdateStatus(
        UpdateLeadStatusRequest $request,
        string $organizationId,
        string $leadId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $lead = $this->leadsService->updateStatusForOrganization(
            $organizationId,
            $leadId,
            $actor,
            (string) $request->validated("status"),
        );

        return StatusResponseFactory::successWithMessage(
            "Lead status updated",
            new LeadResource($lead),
        );
    }

    public function adminIndex(Request $request): JsonResponse
    {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $perPage = max(1, min(100, (int) $request->integer("per_page", 20)));
        $items = $this->leadsService->listForAdmin($actor, $perPage, [
            "status" => (string) $request->query("status", ""),
            "request_for_type" => (string) $request->query("request_for_type", ""),
            "activity_id" => (string) $request->query("activity_id", ""),
            "search" => (string) $request->query("search", ""),
            "sort_by" => (string) $request->query("sort_by", "created_at"),
            "sort_dir" => (string) $request->query("sort_dir", "desc"),
        ]);

        return StatusResponseFactory::success($this->transformPaginator($items));
    }

    public function adminUpdateStatus(
        UpdateLeadStatusRequest $request,
        string $leadId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $lead = $this->leadsService->updateStatusForAdmin(
            $actor,
            $leadId,
            (string) $request->validated("status"),
        );

        return StatusResponseFactory::successWithMessage(
            "Lead status updated",
            new LeadResource($lead),
        );
    }

    private function transformPaginator(LengthAwarePaginator $items): LengthAwarePaginator
    {
        $items->setCollection(
            $items
                ->getCollection()
                ->map(static fn($lead): array => (new LeadResource($lead))->resolve()),
        );

        return $items;
    }
}
