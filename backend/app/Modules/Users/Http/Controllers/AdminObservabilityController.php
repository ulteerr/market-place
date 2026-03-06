<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Services\ObservabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AdminObservabilityController extends Controller
{
    public function __construct(private readonly ObservabilityService $observabilityService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $domain = trim((string) $request->query("domain", ""));
        $limit = max(1, min(200, (int) $request->query("limit", 50)));

        return StatusResponseFactory::success(
            $this->observabilityService->dashboard($domain !== "" ? $domain : null, $limit),
        );
    }
}
