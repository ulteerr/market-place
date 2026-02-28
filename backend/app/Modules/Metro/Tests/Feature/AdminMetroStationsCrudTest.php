<?php

declare(strict_types=1);

namespace Modules\Metro\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Geo\Models\City;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Models\MetroStation;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminMetroStationsCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_admin_metro_stations_routes(): void
    {
        $this->getJson("/api/admin/metro-stations")->assertUnauthorized();
    }

    #[Test]
    public function non_admin_user_cannot_access_admin_metro_stations_routes(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations")
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_list_filter_search_and_sort_metro_stations(): void
    {
        $auth = $this->actingAsAdmin();
        $moscow = City::factory()->create(["name" => "Москва"]);
        $spb = City::factory()->create(["name" => "Санкт-Петербург"]);

        $redLineMoscow = MetroLine::factory()->create([
            "name" => "Сокольническая",
            "line_id" => "1",
            "color" => "#D6083B",
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        $blueLineMoscow = MetroLine::factory()->create([
            "name" => "Арбатско-Покровская",
            "line_id" => "3",
            "color" => "#0078C9",
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        $greenLineSpb = MetroLine::factory()->create([
            "name" => "Невско-Василеостровская",
            "line_id" => "3",
            "color" => "#009A49",
            "city_id" => (string) $spb->id,
            "source" => "manual",
        ]);

        MetroStation::factory()->create([
            "name" => "Охотный ряд",
            "line_id" => "1",
            "metro_line_id" => (string) $redLineMoscow->id,
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        MetroStation::factory()->create([
            "name" => "Арбатская",
            "line_id" => "3",
            "metro_line_id" => (string) $blueLineMoscow->id,
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        MetroStation::factory()->create([
            "name" => "Гостиный двор",
            "line_id" => "3",
            "metro_line_id" => (string) $greenLineSpb->id,
            "city_id" => (string) $spb->id,
            "source" => "manual",
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson(
                "/api/admin/metro-stations?city_id={$moscow->id}&metro_line_id={$blueLineMoscow->id}&search=бат&sort_by=name&sort_dir=asc",
            )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.name", "Арбатская")
            ->assertJsonPath("data.data.0.metro_line_id", (string) $blueLineMoscow->id);
    }

    #[Test]
    public function admin_can_search_by_city_and_line_names_and_sort_city_and_line_by_name(): void
    {
        $auth = $this->actingAsAdmin();
        $moscow = City::factory()->create(["name" => "Москва"]);
        $spb = City::factory()->create(["name" => "Санкт-Петербург"]);

        $lineAlpha = MetroLine::factory()->create([
            "name" => "Альфа",
            "line_id" => "10",
            "color" => "#111111",
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        $lineZulu = MetroLine::factory()->create([
            "name" => "Зулу",
            "line_id" => "20",
            "color" => "#222222",
            "city_id" => (string) $spb->id,
            "source" => "manual",
        ]);

        MetroStation::factory()->create([
            "name" => "Станция Альфа",
            "line_id" => "10",
            "metro_line_id" => (string) $lineAlpha->id,
            "city_id" => (string) $moscow->id,
            "source" => "manual",
        ]);
        MetroStation::factory()->create([
            "name" => "Станция Зулу",
            "line_id" => "20",
            "metro_line_id" => (string) $lineZulu->id,
            "city_id" => (string) $spb->id,
            "source" => "manual",
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations?search=Москва")
            ->assertOk()
            ->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.name", "Станция Альфа");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations?search=Зулу")
            ->assertOk()
            ->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.name", "Станция Зулу");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations?search=20")
            ->assertOk()
            ->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.name", "Станция Зулу");

        $citySortResponse = $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations?sort_by=city_id&sort_dir=asc")
            ->assertOk();
        $citySortedNames = array_values(
            array_map(
                static fn(array $row): string => (string) ($row["name"] ?? ""),
                (array) $citySortResponse->json("data.data"),
            ),
        );
        $citySortedNames = array_intersect($citySortedNames, ["Станция Альфа", "Станция Зулу"]);
        $this->assertSame(["Станция Альфа", "Станция Зулу"], array_values($citySortedNames));

        $lineSortResponse = $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations?sort_by=metro_line_id&sort_dir=asc")
            ->assertOk();
        $lineSortedNames = array_values(
            array_map(
                static fn(array $row): string => (string) ($row["name"] ?? ""),
                (array) $lineSortResponse->json("data.data"),
            ),
        );
        $lineSortedNames = array_intersect($lineSortedNames, ["Станция Альфа", "Станция Зулу"]);
        $this->assertSame(["Станция Альфа", "Станция Зулу"], array_values($lineSortedNames));
    }

    #[Test]
    public function admin_can_crud_metro_station(): void
    {
        $auth = $this->actingAsAdmin();
        $city = City::factory()->create(["name" => "Москва"]);
        $line = MetroLine::factory()->create([
            "name" => "Сокольническая",
            "line_id" => "1",
            "color" => "#D6083B",
            "city_id" => (string) $city->id,
            "source" => "manual",
        ]);

        $createResponse = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/metro-stations", [
                "name" => "Охотный ряд",
                "external_id" => "station-ext-10",
                "line_id" => "1",
                "geo_lat" => 55.757,
                "geo_lon" => 37.615,
                "is_closed" => false,
                "metro_line_id" => (string) $line->id,
                "city_id" => (string) $city->id,
                "source" => "manual",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.name", "Охотный ряд");

        $stationId = (string) $createResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/metro-stations/{$stationId}")
            ->assertOk()
            ->assertJsonPath("data.id", $stationId)
            ->assertJsonPath("data.metro_line_id", (string) $line->id);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/metro-stations/{$stationId}", [
                "name" => "Охотный Ряд",
                "is_closed" => true,
                "geo_lat" => 55.758,
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.name", "Охотный Ряд")
            ->assertJsonPath("data.is_closed", true)
            ->assertJsonPath("data.geo_lat", 55.758);

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/admin/metro-stations/{$stationId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseMissing("metro_stations", ["id" => $stationId]);
    }

    #[Test]
    public function create_metro_station_requires_mandatory_fields_and_valid_ids(): void
    {
        $auth = $this->actingAsAdmin();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/metro-stations", [
                "name" => "",
                "metro_line_id" => "bad-id",
                "city_id" => "bad-id",
                "geo_lat" => 1000,
                "geo_lon" => 1000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                "name",
                "metro_line_id",
                "city_id",
                "source",
                "geo_lat",
                "geo_lon",
            ]);
    }

    private function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();
        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
