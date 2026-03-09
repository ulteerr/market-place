<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organizations\Http\Resources\OrganizationClientResource;
use Modules\Organizations\Services\OrganizationClientsService;
use Modules\Users\Models\User;

final class OrganizationClientController
{
    public function __construct(
        private readonly OrganizationClientsService $organizationClientsService,
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

        $items = $this->organizationClientsService->listForOrganization(
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

    private function transformPaginator(LengthAwarePaginator $items): LengthAwarePaginator
    {
        $items->setCollection(
            $items
                ->getCollection()
                ->map(
                    static fn($client): array => (new OrganizationClientResource(
                        $client,
                    ))->resolve(),
                ),
        );

        return $items;
    }
}
