<?php

declare(strict_types=1);

namespace Modules\Geo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\MetroLine;
use Modules\Geo\Models\MetroStation;
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
        $this->getJson("/api/geo/metro-lines")->assertUnauthorized();
        $this->getJson("/api/geo/metro-stations")->assertUnauthorized();
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

        $lineMoscow = MetroLine::query()->create([
            "name" => "Сокольническая",
            "line_id" => "1",
            "color" => "#D6083B",
            "city_id" => (string) $cityMoscow->id,
            "source" => "manual",
        ]);

        $lineAlmaty = MetroLine::query()->create([
            "name" => "Алматинская",
            "line_id" => "2",
            "color" => "#009A49",
            "city_id" => (string) $cityAlmaty->id,
            "source" => "manual",
        ]);

        MetroStation::query()->create([
            "name" => "Охотный ряд",
            "metro_line_id" => (string) $lineMoscow->id,
            "city_id" => (string) $cityMoscow->id,
            "source" => "manual",
        ]);

        MetroStation::query()->create([
            "name" => "Байконур",
            "metro_line_id" => (string) $lineAlmaty->id,
            "city_id" => (string) $cityAlmaty->id,
            "source" => "manual",
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

        $this->withHeaders($auth["headers"])
            ->getJson("/api/geo/metro-lines?city_id={$cityMoscow->id}")
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.name", "Сокольническая");

        $this->withHeaders($auth["headers"])
            ->getJson("/api/geo/metro-stations?metro_line_id={$lineAlmaty->id}")
            ->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.name", "Байконур");
    }
}
