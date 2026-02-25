<?php

declare(strict_types=1);

namespace Modules\Geo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminGeoCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_admin_geo_routes(): void
    {
        $this->getJson("/api/admin/geo/countries")->assertUnauthorized();
    }

    #[Test]
    public function non_admin_user_cannot_access_admin_geo_routes(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/geo/countries")
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_crud_countries_regions_cities_and_districts(): void
    {
        $auth = $this->actingAsAdmin();

        $countryResponse = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/geo/countries", [
                "name" => "Россия",
                "iso_code" => "RUS",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.name", "Россия");

        $countryId = (string) $countryResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/geo/countries?search=Рос")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/geo/countries/{$countryId}", [
                "name" => "Российская Федерация",
            ])
            ->assertOk()
            ->assertJsonPath("data.name", "Российская Федерация");

        $regionResponse = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/geo/regions", [
                "name" => "Москва",
                "country_id" => $countryId,
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok");
        $regionId = (string) $regionResponse->json("data.id");

        $cityResponse = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/geo/cities", [
                "name" => "Москва",
                "country_id" => $countryId,
                "region_id" => $regionId,
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok");
        $cityId = (string) $cityResponse->json("data.id");

        $districtResponse = $this->withHeaders($auth["headers"])
            ->postJson("/api/admin/geo/districts", [
                "name" => "Центральный",
                "city_id" => $cityId,
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok");
        $districtId = (string) $districtResponse->json("data.id");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/geo/regions?country_id={$countryId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/geo/cities?region_id={$regionId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/geo/districts?city_id={$cityId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/admin/geo/districts/{$districtId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/admin/geo/cities/{$cityId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/admin/geo/regions/{$regionId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/admin/geo/countries/{$countryId}")
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseMissing("countries", ["id" => $countryId]);
        $this->assertDatabaseMissing("regions", ["id" => $regionId]);
        $this->assertDatabaseMissing("cities", ["id" => $cityId]);
        $this->assertDatabaseMissing("districts", ["id" => $districtId]);
    }

    private function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();
        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }
}
