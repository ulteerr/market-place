<?php

declare(strict_types=1);

namespace Modules\Geo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\Region;
use Modules\Geo\Services\CitiesService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PublicCitySelectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_can_search_public_cities(): void
    {
        $country = Country::factory()->create(["name" => "Россия", "iso_code" => "RUS"]);
        $region = Region::factory()->create([
            "name" => "Свердловская область",
            "country_id" => (string) $country->id,
        ]);

        City::factory()->create([
            "name" => "Москва",
            "country_id" => (string) $country->id,
            "region_id" => (string) $region->id,
        ]);
        City::factory()->create([
            "name" => "Екатеринбург",
            "country_id" => (string) $country->id,
            "region_id" => (string) $region->id,
        ]);

        $this->getJson("/api/public/geo/cities?search=Екат&limit=10")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.name", "Екатеринбург")
            ->assertJsonPath("data.0.country.name", "Россия")
            ->assertJsonPath("data.0.region.name", "Свердловская область");
    }

    #[Test]
    public function city_can_be_matched_by_names_with_cyrillic_values(): void
    {
        $country = Country::factory()->create(["name" => "Россия", "iso_code" => "RUS"]);
        $region = Region::factory()->create([
            "name" => "Москва",
            "country_id" => (string) $country->id,
        ]);
        $city = City::factory()->create([
            "name" => "Москва",
            "country_id" => (string) $country->id,
            "region_id" => (string) $region->id,
        ]);

        $matchedCity = app(CitiesService::class)->findByNames("Москва", "Россия", "Москва");

        self::assertNotNull($matchedCity);
        self::assertSame((string) $city->id, (string) $matchedCity->id);
        self::assertSame("Москва", $matchedCity->name);
        self::assertSame("Россия", $matchedCity->country?->name);
    }

    #[Test]
    public function detect_city_falls_back_to_first_city_when_ip_lookup_does_not_match(): void
    {
        config()->set("services.ip_geolocation.base_url", "https://geo.test");

        Http::fake(function (): \Illuminate\Http\Client\Response {
            return Http::response(
                [
                    "success" => true,
                    "city" => "Несуществующий город",
                    "country" => "Россия",
                    "region" => "Неизвестный регион",
                ],
                200,
            );
        });

        $country = Country::factory()->create(["name" => "Россия", "iso_code" => "RUS"]);
        $region = Region::factory()->create([
            "name" => "Свердловская область",
            "country_id" => (string) $country->id,
        ]);
        $city = City::factory()->create([
            "name" => "Екатеринбург",
            "country_id" => (string) $country->id,
            "region_id" => (string) $region->id,
        ]);

        $this->call(
            "GET",
            "/api/public/geo/detect-city",
            [],
            [],
            [],
            [
                "REMOTE_ADDR" => "203.0.113.11",
                "HTTP_ACCEPT" => "application/json",
            ],
        )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.resolved_by", "fallback")
            ->assertJsonPath("data.city.id", (string) $city->id)
            ->assertJsonPath("data.city.name", "Екатеринбург");
    }
}
