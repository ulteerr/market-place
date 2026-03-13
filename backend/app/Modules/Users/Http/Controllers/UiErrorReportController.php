<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Services\ObservabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Users\Http\Requests\StoreUiErrorReportRequest;
use Modules\Users\Models\UiErrorReport;
use Modules\Users\Models\User;
use Modules\Users\Notifications\UiErrorReportSubmittedNotification;
use Modules\Users\Services\UiErrorReportSanitizer;

final class UiErrorReportController extends Controller
{
    public function __construct(
        private readonly ObservabilityService $observabilityService,
        private readonly UiErrorReportSanitizer $sanitizer,
    ) {}

    public function __invoke(StoreUiErrorReportRequest $request): JsonResponse
    {
        $payload = $this->sanitizer->sanitize($request->validated());
        $resolvedUser = $request->user();

        if ($resolvedUser === null) {
            $bearerToken = $request->bearerToken();
            if (is_string($bearerToken) && $bearerToken !== "") {
                $tokenModel = PersonalAccessToken::findToken($bearerToken);
                if ($tokenModel !== null) {
                    $resolvedUser = $tokenModel->tokenable;
                }
            }
        }

        $report = UiErrorReport::query()->create([
            "user_id" => $resolvedUser?->id,
            "status" => "received",
            "page_url" => $payload["page"]["url"] ?? null,
            "route_name" => $payload["page"]["routeName"] ?? null,
            "block_id" => $payload["block"]["id"] ?? "",
            "description" => $payload["description"] ?? "",
            "attachments" => $payload["attachments"] ?? [],
            "payload" => $payload,
        ]);

        $this->notifyAdmins($report);

        $this->observabilityService->recordEvent(
            "support",
            "ui.error.reporter",
            "ui_error_report_submitted",
            "ok",
            "warning",
            null,
            [
                "report_id" => $report->id,
                "user_id" => $resolvedUser?->id,
                "route_name" => $payload["page"]["routeName"] ?? null,
                "block_id" => $payload["block"]["id"] ?? null,
                "attachments_count" => count($payload["attachments"] ?? []),
            ],
        );

        return StatusResponseFactory::successWithMessage(
            "UI error report accepted",
            [
                "reportId" => $report->id,
                "status" => $report->status,
            ],
            201,
        );
    }

    private function notifyAdmins(UiErrorReport $report): void
    {
        /** @var Collection<int, User> $admins */
        $admins = User::query()
            ->with(["roles.permissions:id,code", "permissionOverrides.permission:id,code"])
            ->get()
            ->filter(fn(User $user): bool => $user->hasPermission("admin.panel.access"))
            ->unique("id")
            ->values();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new UiErrorReportSubmittedNotification($report));
    }
}
