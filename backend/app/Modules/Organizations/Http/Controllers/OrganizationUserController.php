<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organizations\Http\Requests\CreateOrganizationUserRequest;
use Modules\Organizations\Http\Requests\UpdateOrganizationUserRequest;
use Modules\Organizations\Services\OrganizationUsersService;
use Modules\Users\Models\User;

final class OrganizationUserController
{
    public function __construct(
        private readonly OrganizationUsersService $organizationUsersService,
    ) {}

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

        $items = $this->organizationUsersService->listForOrganization(
            $organizationId,
            $actor,
            $perPage,
            [
                "search" => (string) $request->query("search", ""),
                "status" => (string) $request->query("status", ""),
                "sort_by" => $sortBy,
                "sort_dir" => $sortDir,
            ],
        );

        return StatusResponseFactory::success($items);
    }

    public function store(
        CreateOrganizationUserRequest $request,
        string $organizationId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $member = $this->organizationUsersService->addMember(
            $organizationId,
            $actor,
            (string) $request->validated("user_id"),
            $request->validated("position"),
            (string) $request->validated("status", "active"),
        );

        return StatusResponseFactory::successWithMessage(
            "Organization member created",
            $member,
            201,
        );
    }

    public function update(
        UpdateOrganizationUserRequest $request,
        string $organizationId,
        string $memberId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $member = $this->organizationUsersService->updateMember(
            $organizationId,
            $memberId,
            $actor,
            $request->validated(),
        );

        return StatusResponseFactory::successWithMessage("Organization member updated", $member);
    }

    public function destroy(
        string $organizationId,
        string $memberId,
        Request $request,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $this->organizationUsersService->removeMember($organizationId, $memberId, $actor);

        return StatusResponseFactory::ok("Deleted successfully");
    }
}
