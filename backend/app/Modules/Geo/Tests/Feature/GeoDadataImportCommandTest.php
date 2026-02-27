<?php

declare(strict_types=1);

namespace Modules\Geo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GeoDadataImportCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_russia_geo_data_from_dadata(): void
    {
        config()->set("services.dadata.token", "test-token");
        config()->set("services.dadata.base_url", "https://suggestions.dadata.ru");
        config()->set("services.dadata.secret", "");

        Http::fake(function (Request $request) {
            $payload = $request->data();
            $bound = (string) ($payload["from_bound"]["value"] ?? "");
            $query = (string) ($payload["query"] ?? "");

            if ($query !== "") {
                return Http::response(["suggestions" => []], 200);
            }

            if ($bound === "region") {
                return Http::response(
                    [
                        "suggestions" => [
                            [
                                "data" => [
                                    "region" => "Москва",
                                    "region_fias_id" => "reg-fias-1",
                                ],
                            ],
                        ],
                    ],
                    200,
                );
            }

            if ($bound === "city") {
                return Http::response(
                    [
                        "suggestions" => [
                            [
                                "data" => [
                                    "city" => "Москва",
                                    "city_fias_id" => "city-fias-1",
                                    "city_district" => "центральный",
                                ],
                            ],
                        ],
                    ],
                    200,
                );
            }

            if ($bound === "settlement") {
                return Http::response(["suggestions" => []], 200);
            }

            return Http::response(["suggestions" => []], 200);
        });

        $this->artisan(
            "geo:import-russia-dadata --max-prefix-depth=1 --max-requests=50 --sleep-ms=0",
        )->assertSuccessful();

        $country = Country::query()->where("iso_code", "RUS")->first();
        $this->assertNotNull($country);
        $this->assertSame("Россия", $country->name);

        $region = Region::query()
            ->where("name", "Москва")
            ->where("country_id", (string) $country->id)
            ->first();
        $this->assertNotNull($region);

        $city = City::query()
            ->where("name", "Москва")
            ->where("country_id", (string) $country->id)
            ->where("region_id", (string) $region->id)
            ->first();
        $this->assertNotNull($city);

        $district = District::query()
            ->where("city_id", (string) $city->id)
            ->get()
            ->first(fn(District $item) => mb_strtolower($item->name) === "центральный");
        $this->assertNotNull($district);
    }
}
