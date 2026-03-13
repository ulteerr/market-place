<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Users\Enums\RoleCode;
use Modules\Users\Models\Role;
use Modules\Users\Models\UiErrorReport;
use Modules\Users\Models\User;
use Modules\Users\Notifications\UiErrorReportSubmittedNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UiErrorReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            "page" => [
                "url" => "https://example.test/catalog/football",
                "path" => "/catalog/football",
                "routeName" => "catalog-slug",
            ],
            "block" => [
                "id" => "public-header",
                "strategy" => "data-test",
                "queryPath" => "header:nth-of-type(1)",
                "selectedAt" => "2026-03-11T10:00:00.000Z",
            ],
            "description" => "Клик по каталогу не открывает меню.",
            "attachments" => [
                [
                    "name" => "screen.png",
                    "safeName" => "screen.png",
                    "type" => "image/png",
                    "size" => 12345,
                ],
            ],
            "context" => [
                "userAgent" => "Playwright/1.0",
                "viewport" => [
                    "width" => 1366,
                    "height" => 900,
                ],
                "theme" => "dark",
                "locale" => "ru",
                "timestamp" => "2026-03-11T10:01:00.000Z",
            ],
        ];
    }

    #[Test]
    public function guest_can_submit_ui_error_report(): void
    {
        $response = $this->postJson("/api/reports/ui-errors", $this->validPayload());

        $response
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.status", "received")
            ->assertJsonStructure(["status", "message", "data" => ["reportId", "status"]]);

        $reportId = (string) $response->json("data.reportId");
        $this->assertNotSame("", $reportId);

        $this->assertDatabaseHas("ui_error_reports", [
            "id" => $reportId,
            "status" => "received",
            "block_id" => "public-header",
            "user_id" => null,
        ]);
    }

    #[Test]
    public function authenticated_user_submission_records_user_id(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->postJson(
            "/api/reports/ui-errors",
            $this->validPayload(),
        );

        $response->assertStatus(201);
        $reportId = (string) $response->json("data.reportId");

        $report = UiErrorReport::query()->findOrFail($reportId);
        $this->assertSame((string) $auth["user"]->id, (string) $report->user_id);
    }

    #[Test]
    public function it_notifies_only_admin_users_about_new_ui_error_report(): void
    {
        Notification::fake();

        $adminRole = Role::factory()
            ->admin()
            ->create([
                "code" => RoleCode::ADMIN->value,
            ]);

        $admin = User::factory()->create([
            "email" => "admin-ui-report@example.com",
        ]);
        $admin->roles()->syncWithoutDetaching([(string) $adminRole->id]);

        $regularUser = User::factory()->create([
            "email" => "participant-ui-report@example.com",
        ]);

        $response = $this->postJson("/api/reports/ui-errors", $this->validPayload());
        $response->assertStatus(201);

        Notification::assertSentTo($admin, UiErrorReportSubmittedNotification::class);
        Notification::assertNotSentTo($regularUser, UiErrorReportSubmittedNotification::class);
    }

    #[Test]
    public function it_masks_sensitive_payload_fields_before_storing(): void
    {
        $payload = $this->validPayload();
        $payload["description"] =
            "Свяжитесь со мной user@example.com и проверьте Bearer abc.def.ghi token=secret123";
        $payload["page"]["url"] = "https://example.test/catalog/football?access_token=verysecret";

        $response = $this->postJson("/api/reports/ui-errors", $payload);
        $response->assertStatus(201);

        $reportId = (string) $response->json("data.reportId");
        $report = UiErrorReport::query()->findOrFail($reportId);

        $storedPayload = is_array($report->payload) ? $report->payload : [];
        $this->assertStringNotContainsString(
            "user@example.com",
            (string) ($storedPayload["description"] ?? ""),
        );
        $this->assertStringContainsString(
            "[masked]",
            (string) ($storedPayload["description"] ?? ""),
        );
        $this->assertStringContainsString(
            "access_token=[masked]",
            (string) ($storedPayload["page"]["url"] ?? ""),
        );
    }

    #[Test]
    public function it_rejects_forbidden_attachment_extensions(): void
    {
        $payload = $this->validPayload();
        $payload["attachments"][0]["safeName"] = "malware.exe";
        $payload["attachments"][0]["name"] = "malware.exe";

        $this->postJson("/api/reports/ui-errors", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["attachments.0.safeName"]);
    }

    #[Test]
    public function it_applies_rate_limit_for_ui_error_reports_endpoint(): void
    {
        $payload = $this->validPayload();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson("/api/reports/ui-errors", $payload)->assertStatus(201);
        }

        $this->postJson("/api/reports/ui-errors", $payload)->assertStatus(429);
    }
}
