<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organizations\Http\Requests\CreateOrganizationJoinRequest;
use Modules\Organizations\Http\Requests\ReviewOrganizationJoinRequest;
use Modules\Organizations\Http\Resources\OrganizationJoinRequestResource;
use Modules\Organizations\Services\OrganizationJoinRequestsService;
use Modules\Users\Models\User;

final class OrganizationJoinRequestController
{
    public function __construct(
        private readonly OrganizationJoinRequestsService $organizationJoinRequestsService,
    ) {}

    public function submit(
        CreateOrganizationJoinRequest $request,
        string $organizationId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();

        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $joinRequest = $this->organizationJoinRequestsService->submit(
            $organizationId,
            $actor,
            (string) $request->validated("subject_type"),
            (string) $request->validated("subject_id"),
            $request->validated("message"),
        );
        $joinRequest->loadMissing(["requestedBy", "subjectUser", "subjectChild", "reviewedBy"]);

        return StatusResponseFactory::successWithMessage(
            "Join request submitted",
            new OrganizationJoinRequestResource($joinRequest),
            201,
        );
    }

    public function index(Request $request, string $organizationId): JsonResponse
    {
        /** @var User|null $actor */
        $actor = $request->user();

        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $perPage = max(1, min(100, (int) $request->integer("per_page", 20)));
        $sortBy = trim((string) $request->query("sort_by", "created_at"));
        $sortDir = strtolower((string) $request->query("sort_dir", "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $items = $this->organizationJoinRequestsService->listForOrganization(
            $organizationId,
            $actor,
            $perPage,
            [
                "status" => (string) $request->query("status", ""),
                "subject_type" => (string) $request->query("subject_type", ""),
                "search" => (string) $request->query("search", ""),
                "sort_by" => $sortBy,
                "sort_dir" => $sortDir,
            ],
        );

        return StatusResponseFactory::success($this->transformPaginator($items));
    }

    public function my(Request $request, string $organizationId): JsonResponse
    {
        /** @var User|null $actor */
        $actor = $request->user();

        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $perPage = max(1, min(100, (int) $request->integer("per_page", 20)));
        $sortBy = trim((string) $request->query("sort_by", "created_at"));
        $sortDir = strtolower((string) $request->query("sort_dir", "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $items = $this->organizationJoinRequestsService->myRequests(
            $organizationId,
            $actor,
            $perPage,
            [
                "status" => (string) $request->query("status", ""),
                "sort_by" => $sortBy,
                "sort_dir" => $sortDir,
            ],
        );

        return StatusResponseFactory::success($this->transformPaginator($items));
    }

    public function approve(
        ReviewOrganizationJoinRequest $request,
        string $organizationId,
        string $requestId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();

        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $joinRequest = $this->organizationJoinRequestsService->approve(
            $organizationId,
            $requestId,
            $actor,
            $request->validated("review_note"),
        );

        return StatusResponseFactory::successWithMessage(
            "Join request approved",
            new OrganizationJoinRequestResource($joinRequest),
        );
    }

    public function reject(
        ReviewOrganizationJoinRequest $request,
        string $organizationId,
        string $requestId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();

        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $joinRequest = $this->organizationJoinRequestsService->reject(
            $organizationId,
            $requestId,
            $actor,
            $request->validated("review_note"),
        );

        return StatusResponseFactory::successWithMessage(
            "Join request rejected",
            new OrganizationJoinRequestResource($joinRequest),
        );
    }

    private function transformPaginator(LengthAwarePaginator $items): LengthAwarePaginator
    {
        $items->setCollection(
            $items
                ->getCollection()
                ->map(
                    static fn($joinRequest): array => (new OrganizationJoinRequestResource(
                        $joinRequest,
                    ))->resolve(),
                ),
        );

        return $items;
    }
}
