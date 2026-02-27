<?php

declare(strict_types=1);

namespace Modules\Metro\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Geo\Models\City;
use Modules\Metro\Models\MetroLine;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminMetroLinesCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_admin_metro_lines_routes(): void
    {
        $this->getJson("/api/admin/metro-lines")->assertUnauthorized();
    }

    #[Test]
    public function non_admin_user_cannot_access_admin_metro_lines_routes(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])->getJson("/api/admin/metro-lines")->assertForbidden();
    }

    #[Test]
    public function admin_can_list_filter_search_and_sort_metro_lines(): void
    {
        $auth = $this->actingAsAdmin();
        $moscow = City::factory()->create(["name" => "Москва"]);
        $spb = City::factory()->create(["name" => "Санкт-Петербург"]);

        MetroLine::factory()->create([
            "name" => "Сокольническая",
            "line_id" => "1",
            "color" => "#D6083B",
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        MetroLine::factory()->create([
            "name" => "Арбатско-Покровская",
            "line_id" => "3",
            "color" => "#0078C9",
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        MetroLine::factory()->create([
            "name" => "Невско-Василеостровская",
            "line_id" => "3",
            "color" => "#009A49",
            "city_id" => (string) $spb->id,
            "source" => "manual",
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson(
                "/api/admin/metro-lines?city_id={$moscow->id}&search=ская&sort_by=name&sort_dir=asc",
            )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.total", 2)
            ->assertJsonPath("data.data.0.name", "Арбатско-Покровская")
            ->assertJsonPath("data.data.1.name", "Сокольническая");
    }

    #[Test]
    public function admin_can_crud_metro_line(): void
    {
        $auth = $this->actingAsAdmin();
        $city = City::factory()->create(["name" => "Москва"]);

        $createResponse = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/metro-lines", [
                "name" => "Кольцевая",
                "external_id" => "line-ext-10",
                "line_id" => "5",
                "color" => "#915133",
                "city_id" => (string) $city->id,
                "source" => "manual",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.name", "Кольцевая");

        $lineId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-lines/{$lineId}")
            ->assertOk()
            ->assertJsonPath("data.id", $lineId)
            ->assertJsonPath("data.color", "#915133");

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/metro-lines/{$lineId}", [
                "name" => "Большая кольцевая",
                "color" => "#88AA22",
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.name", "Большая кольцевая")
            ->assertJsonPath("data.color", "#88AA22");

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/admin/metro-lines/{$lineId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseMissing("metro_lines", ["id" => $lineId]);
    }

    #[Test]
    public function create_metro_line_requires_mandatory_fields_and_valid_city_id(): void
    {
        $auth = $this->actingAsAdmin();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/metro-lines", [
                "name" => "",
                "city_id" => "not-a-uuid",
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["name", "city_id", "source"]);
    }

    private function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();
        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
