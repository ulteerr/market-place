<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateMeProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_update_profile_and_email(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->patchJson("/api/me", [
            "first_name" => "Иван",
            "last_name" => "Петров",
            "middle_name" => "Иванович",
            "gender" => "male",
            "phone" => "+79990001122",
            "birth_date" => "1990-01-15",
            "email" => "new-email@example.com",
        ]);

        $response
            ->assertOk()
            ->assertJsonPath("user.first_name", "Иван")
            ->assertJsonPath("user.last_name", "Петров")
            ->assertJsonPath("user.middle_name", "Иванович")
            ->assertJsonPath("user.gender", "male")
            ->assertJsonPath("user.phone", "+79990001122")
            ->assertJsonPath("user.birth_date", "1990-01-15")
            ->assertJsonPath("user.email", "new-email@example.com");

        $auth["user"]->refresh();

        $this->assertSame("Иван", $auth["user"]->first_name);
        $this->assertSame("Петров", $auth["user"]->last_name);
        $this->assertSame("Иванович", $auth["user"]->middle_name);
        $this->assertSame("male", $auth["user"]->gender);
        $this->assertSame("+79990001122", $auth["user"]->phone);
        $this->assertSame("1990-01-15", optional($auth["user"]->birth_date)->format("Y-m-d"));
        $this->assertSame("new-email@example.com", $auth["user"]->email);
    }

    #[Test]
    public function email_must_be_unique_when_updating_profile(): void
    {
        $auth = $this->actingAsUser();
        User::factory()->create(["email" => "taken@example.com"]);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/me", [
                "email" => "taken@example.com",
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    #[Test]
    public function guest_cannot_update_profile(): void
    {
        $this->patchJson("/api/me", [
            "first_name" => "Guest",
            "email" => "guest@example.com",
        ])->assertUnauthorized();
    }

    #[Test]
    public function birth_date_cannot_be_in_future_when_updating_profile(): void
    {
        Config::set("birth-date.users.disallow_future", true);
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/me", [
                "birth_date" => now()->addDay()->format("Y-m-d"),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["birth_date"]);
    }

    #[Test]
    public function birth_date_must_match_configured_minimum_age_when_updating_profile(): void
    {
        Config::set("birth-date.users.min_age_years", 14);
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/me", [
                "birth_date" => now()->subYears(13)->format("Y-m-d"),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["birth_date"]);
    }
}
