<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateMeSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_update_settings(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "locale" => "en",
                "theme" => "dark",
                "collapse_menu" => true,
                "admin_crud_preferences" => [
                    "users" => [
                        "contentMode" => "cards",
                        "tableOnDesktop" => false,
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath("user.settings.locale", "en")
            ->assertJsonPath("user.settings.theme", "dark")
            ->assertJsonPath("user.settings.collapse_menu", true)
            ->assertJsonPath("user.settings.admin_crud_preferences.users.contentMode", "cards")
            ->assertJsonPath("user.settings.admin_crud_preferences.users.tableOnDesktop", false);

        $auth["user"]->refresh();

        $this->assertSame("en", $auth["user"]->settings["locale"] ?? null);
        $this->assertSame("dark", $auth["user"]->settings["theme"] ?? null);
        $this->assertTrue($auth["user"]->settings["collapse_menu"] ?? false);
        $this->assertSame(
            "cards",
            $auth["user"]->settings["admin_crud_preferences"]["users"]["contentMode"] ?? null,
        );
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
    public function guest_cannot_update_settings(): void
    {
        $this->patchJson("/api/me/settings", [
            "settings" => [
                "theme" => "dark",
            ],
        ])->assertUnauthorized();
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
                "admin_crud_preferences" => [
                    "users" => [
                        "contentMode" => "cards",
                        "tableOnDesktop" => false,
                    ],
                ],
            ],
        ]);

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me/settings", [
            "settings" => [
                "theme" => "dark",
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath("user.settings.locale", "ru")
            ->assertJsonPath("user.settings.theme", "dark")
            ->assertJsonPath("user.settings.collapse_menu", true)
            ->assertJsonPath("user.settings.admin_crud_preferences.users.contentMode", "cards")
            ->assertJsonPath("user.settings.admin_crud_preferences.users.tableOnDesktop", false);
    }
}
