<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organizations\Http\Requests\TransferOrganizationOwnershipRequest;
use Modules\Organizations\Services\OrganizationsService;
use Modules\Users\Models\User;

final class OrganizationController
{
    public function __construct(private readonly OrganizationsService $organizationsService) {}

    public function my(Request $request): JsonResponse
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

        $items = $this->organizationsService->myOrganizations(
            $actor,
            $perPage,
            [],
            [
                "search" => (string) $request->query("search", ""),
                "sort_by" => $sortBy,
                "sort_dir" => $sortDir,
            ],
        );

        return StatusResponseFactory::success($items);
    }

    public function transferOwnership(
        TransferOrganizationOwnershipRequest $request,
        string $organizationId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $organization = $this->organizationsService->transferOwnership(
            $organizationId,
            $actor,
            (string) $request->validated("target_user_id"),
        );

        return StatusResponseFactory::successWithMessage(
            "Organization ownership transferred",
            $organization,
        );
    }
}
