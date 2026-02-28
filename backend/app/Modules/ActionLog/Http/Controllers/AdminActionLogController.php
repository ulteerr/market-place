<?php

declare(strict_types=1);

namespace Modules\ActionLog\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ActionLog\Http\Resources\ActionLogResource;
use Modules\ActionLog\Services\ActionLogService;

final class AdminActionLogController extends Controller
{
    public function __construct(private readonly ActionLogService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer("per_page", 20)));
        $page = max(1, (int) $request->integer("page", 1));

        $items = $this->service->paginateForAdmin(
            [
                "event" => $request->query("event"),
                "model" => $request->query("model"),
                "model_id" => $request->query("model_id"),
                "user" => $request->query("user"),
                "search" => $request->query("search"),
                "date_from" => $request->query("date_from"),
                "date_to" => $request->query("date_to"),
                "sort_by" => $request->query("sort_by"),
                "sort_dir" => $request->query("sort_dir"),
            ],
            $perPage,
            $page,
        );

        return StatusResponseFactory::success([
            ...$items->toArray(),
            "data" => ActionLogResource::collection($items->getCollection())->resolve(),
        ]);
    }
}
