<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ActionLog\Models\ActionLog;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Users\Events\MeSettingsUpdated;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateMeSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_update_settings(): void
    {
        $auth = $this->actingAsUser();
        $userId = (string) $auth["user"]->id;

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "locale" => "en",
                "theme" => "dark",
                "collapse_menu" => true,
                "favorites" => ["act-public-1", "act-public-2"],
                "public_city" => [
                    "city_id" => "11111111-1111-1111-1111-111111111111",
                    "city_name" => "Москва",
                    "source" => "manual",
                    "region_id" => "22222222-2222-2222-2222-222222222222",
                    "region_name" => "Москва",
                    "country_id" => "33333333-3333-3333-3333-333333333333",
                    "country_name" => "Россия",
                ],
                "admin_crud_preferences" => [
                    "users" => [
                        "contentMode" => "cards",
                        "tableOnDesktop" => false,
                    ],
                ],
                "admin_navigation_sections" => [
                    "system" => [
                        "open" => true,
                    ],
                ],
            ],
        ]);

        $response->assertNoContent();

        $auth["user"]->refresh();

        $this->assertSame("en", $auth["user"]->settings["locale"] ?? null);
        $this->assertSame("dark", $auth["user"]->settings["theme"] ?? null);
        $this->assertTrue($auth["user"]->settings["collapse_menu"] ?? false);
        $this->assertSame(
            ["act-public-1", "act-public-2"],
            $auth["user"]->settings["favorites"] ?? [],
        );
        $this->assertSame(
            "11111111-1111-1111-1111-111111111111",
            $auth["user"]->settings["public_city"]["city_id"] ?? null,
        );
        $this->assertSame("Москва", $auth["user"]->settings["public_city"]["city_name"] ?? null);
        $this->assertSame("manual", $auth["user"]->settings["public_city"]["source"] ?? null);
        $this->assertSame(
            "cards",
            $auth["user"]->settings["admin_crud_preferences"]["users"]["contentMode"] ?? null,
        );
        $this->assertFalse(
            $auth["user"]->settings["admin_crud_preferences"]["users"]["tableOnDesktop"] ?? true,
        );
        $this->assertTrue(
            $auth["user"]->settings["admin_navigation_sections"]["system"]["open"] ?? false,
        );

        $settingsChangeLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", $userId)
            ->where("event", "update")
            ->latest("created_at")
            ->first();
        $this->assertNull($settingsChangeLog);

        $settingsActionLog = ActionLog::query()
            ->where("model_type", User::class)
            ->where("model_id", $userId)
            ->where("event", "update")
            ->latest("created_at")
            ->first();
        $this->assertNull($settingsActionLog);
    }

    #[Test]
    public function invalid_content_mode_returns_validation_error(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "admin_crud_preferences" => [
                    "users" => [
                        "contentMode" => "grid",
                    ],
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(["settings.admin_crud_preferences.users.contentMode"]);
    }

    #[Test]
    public function invalid_collapse_menu_returns_validation_error(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "collapse_menu" => "yes",
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(["settings.collapse_menu"]);
    }

    #[Test]
    public function invalid_navigation_section_open_returns_validation_error(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "admin_navigation_sections" => [
                    "system" => [
                        "open" => "yes",
                    ],
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(["settings.admin_navigation_sections.system.open"]);
    }

    #[Test]
    public function invalid_locale_returns_validation_error(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "locale" => "de",
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(["settings.locale"]);
    }

    #[Test]
    public function invalid_favorites_item_returns_validation_error(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "favorites" => ["act-1", 123],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(["settings.favorites.1"]);
    }

    #[Test]
    public function invalid_public_city_source_returns_validation_error(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "public_city" => [
                    "city_id" => "11111111-1111-1111-1111-111111111111",
                    "city_name" => "Москва",
                    "source" => "fallback",
                    "country_id" => "33333333-3333-3333-3333-333333333333",
                    "country_name" => "Россия",
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(["settings.public_city.source"]);
    }

    #[Test]
    public function guest_cannot_update_settings(): void
    {
        $this->patchJson("/api/me/settings", [
            "settings" => [
                "theme" => "dark",
            ],
        ])->assertUnauthorized();
    }

    #[Test]
    public function settings_update_dispatches_me_settings_updated_event(): void
    {
        Event::fake([MeSettingsUpdated::class]);
        $auth = $this->actingAsUser();
        $userId = (string) $auth["user"]->id;

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "theme" => "dark",
            ],
        ]);

        $response->assertNoContent();

        Event::assertDispatched(MeSettingsUpdated::class, function (MeSettingsUpdated $event) use (
            $userId,
        ): bool {
            $channelName = $event->broadcastOn()->name;
            $payload = $event->broadcastWith();

            return $event->broadcastAs() === "me.settings.updated" &&
                str_contains($channelName, "me-settings.{$userId}") &&
                ($payload["user_id"] ?? null) === $userId &&
                ($payload["settings"]["theme"] ?? null) === "dark" &&
                is_string($payload["updated_at"] ?? null) &&
                is_int($payload["version"] ?? null);
        });
    }

    #[Test]
    public function partial_settings_update_preserves_existing_keys(): void
    {
        $auth = $this->actingAsUser();
        $auth["user"]->update([
            "settings" => [
                "locale" => "ru",
                "theme" => "light",
                "collapse_menu" => true,
                "favorites" => ["act-public-1", "act-public-2"],
                "public_city" => [
                    "city_id" => "11111111-1111-1111-1111-111111111111",
                    "city_name" => "Москва",
                    "source" => "manual",
                    "region_id" => "22222222-2222-2222-2222-222222222222",
                    "region_name" => "Москва",
                    "country_id" => "33333333-3333-3333-3333-333333333333",
                    "country_name" => "Россия",
                ],
                "admin_crud_preferences" => [
                    "users" => [
                        "contentMode" => "cards",
                        "tableOnDesktop" => false,
                    ],
                ],
                "admin_navigation_sections" => [
                    "system" => [
                        "open" => true,
                    ],
                ],
            ],
        ]);

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "theme" => "dark",
            ],
        ]);

        $response->assertNoContent();

        $auth["user"]->refresh();

        $this->assertSame("ru", $auth["user"]->settings["locale"] ?? null);
        $this->assertSame("dark", $auth["user"]->settings["theme"] ?? null);
        $this->assertTrue($auth["user"]->settings["collapse_menu"] ?? false);
        $this->assertSame(
            ["act-public-1", "act-public-2"],
            $auth["user"]->settings["favorites"] ?? [],
        );
        $this->assertSame(
            "11111111-1111-1111-1111-111111111111",
            $auth["user"]->settings["public_city"]["city_id"] ?? null,
        );
        $this->assertSame(
            "cards",
            $auth["user"]->settings["admin_crud_preferences"]["users"]["contentMode"] ?? null,
        );
        $this->assertFalse(
            $auth["user"]->settings["admin_crud_preferences"]["users"]["tableOnDesktop"] ?? true,
        );
        $this->assertTrue(
            $auth["user"]->settings["admin_navigation_sections"]["system"]["open"] ?? false,
        );
    }
}
