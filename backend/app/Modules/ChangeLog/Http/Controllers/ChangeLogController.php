<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ChangeLog\Http\Resources\ChangeLogResource;
use Modules\ChangeLog\Services\ChangeLogService;
use RuntimeException;

final class ChangeLogController extends Controller
{
    public function __construct(private readonly ChangeLogService $service) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->paginate(
            [
                "model" => $request->query("model"),
                "entity_id" => $request->query("entity_id"),
                "event" => $request->query("event"),
            ],
            (int) $request->integer("per_page", 30),
        );

        return StatusResponseFactory::success([
            ...$items->toArray(),
            "data" => ChangeLogResource::collection($items->getCollection())->resolve(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->service->findById($id);
        if (!$entry) {
            return StatusResponseFactory::error("ChangeLog entry not found.", 404);
        }

        return StatusResponseFactory::success(new ChangeLogResource($entry));
    }

    public function rollback(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole("admin")) {
            return StatusResponseFactory::error("Forbidden", 403);
        }

        $entry = $this->service->findById($id);
        if (!$entry) {
            return StatusResponseFactory::error("ChangeLog entry not found.", 404);
        }

        try {
            $model = $this->service->rollback($entry);
        } catch (RuntimeException $exception) {
            return StatusResponseFactory::error($exception->getMessage(), 409);
        }

        return StatusResponseFactory::successWithMessage("Rollback completed.", [
            "model_type" => $model::class,
            "model_id" => (string) $model->getKey(),
            "rolled_back_from_id" => $entry->id,
        ]);
    }
}
