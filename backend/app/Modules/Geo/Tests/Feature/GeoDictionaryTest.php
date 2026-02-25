<?php

declare(strict_types=1);

namespace Modules\Geo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GeoDictionaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_geo_dictionary_endpoints(): void
    {
        $this->getJson("/api/geo/countries")->assertUnauthorized();
        $this->getJson("/api/geo/regions")->assertUnauthorized();
        $this->getJson("/api/geo/cities")->assertUnauthorized();
        $this->getJson("/api/geo/districts")->assertUnauthorized();
    }

    #[Test]
    public function authenticated_user_can_list_and_filter_geo_dictionary(): void
    {
        $auth = $this->actingAsUser();

        $countryRu = Country::factory()->create(["name" => "Россия", "iso_code" => "RUS"]);
        $countryKz = Country::factory()->create(["name" => "Казахстан", "iso_code" => "KAZ"]);

        $regionMoscow = Region::factory()->create([
            "name" => "Москва",
            "country_id" => (string) $countryRu->id,
        ]);
        $regionAlmaty = Region::factory()->create([
            "name" => "Алматинская область",
            "country_id" => (string) $countryKz->id,
        ]);

        $cityMoscow = City::factory()->create([
            "name" => "Москва",
            "region_id" => (string) $regionMoscow->id,
            "country_id" => (string) $countryRu->id,
        ]);
        $cityAlmaty = City::factory()->create([
            "name" => "Алматы",
            "region_id" => (string) $regionAlmaty->id,
            "country_id" => (string) $countryKz->id,
        ]);

        District::factory()->create([
            "name" => "Центральный",
            "city_id" => (string) $cityMoscow->id,
        ]);
        District::factory()->create([
            "name" => "Алмалинский",
            "city_id" => (string) $cityAlmaty->id,
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/geo/countries?search=Рос")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/geo/regions?country_id={$countryRu->id}")
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.name", "Москва");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/geo/cities?region_id={$regionAlmaty->id}")
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.name", "Алматы");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/geo/districts?city_id={$cityMoscow->id}")
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.name", "Центральный");
    }
}
