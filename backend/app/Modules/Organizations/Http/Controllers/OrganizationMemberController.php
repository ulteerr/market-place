<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organizations\Http\Requests\CreateOrganizationMemberRequest;
use Modules\Organizations\Http\Requests\UpdateOrganizationMemberRequest;
use Modules\Organizations\Services\OrganizationMembersService;
use Modules\Users\Models\User;

final class OrganizationMemberController
{
    public function __construct(
        private readonly OrganizationMembersService $organizationMembersService,
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

        $items = $this->organizationMembersService->listForOrganization(
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
        CreateOrganizationMemberRequest $request,
        string $organizationId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $member = $this->organizationMembersService->addMember(
            $organizationId,
            $actor,
            (string) $request->validated("user_id"),
            (string) $request->validated("role_code", "member"),
            (string) $request->validated("status", "active"),
        );

        return StatusResponseFactory::successWithMessage(
            "Organization member created",
            $member,
            201,
        );
    }

    public function update(
        UpdateOrganizationMemberRequest $request,
        string $organizationId,
        string $memberId,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        if (!$actor) {
            return StatusResponseFactory::error("Unauthorized", 401);
        }

        $member = $this->organizationMembersService->updateMember(
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

        $this->organizationMembersService->removeMember($organizationId, $memberId, $actor);

        return StatusResponseFactory::ok("Deleted successfully");
    }
}
