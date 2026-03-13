<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Users\Http\Requests\AdminCacheResetRequest;
use Modules\Users\Services\AdminCacheResetService;

final class AdminCacheResetController extends Controller
{
    public function __construct(private readonly AdminCacheResetService $cacheResetService) {}

    public function __invoke(AdminCacheResetRequest $request): JsonResponse
    {
        $scopes = $this->cacheResetService->reset($request->validated("scopes"));

        return StatusResponseFactory::successWithMessage("Admin cache reset completed", [
            "scopes" => $scopes,
            "status" => "completed",
        ]);
    }
}
