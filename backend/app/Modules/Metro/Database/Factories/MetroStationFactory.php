<?php

declare(strict_types=1);

namespace Modules\Metro\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Geo\Models\City;
use Modules\Metro\Models\MetroLine;
use Modules\Metro\Models\MetroStation;

/**
 * @extends Factory<MetroStation>
 */
final class MetroStationFactory extends Factory
{
    protected $model = MetroStation::class;

    public function definition(): array
    {
        return [
            "name" => "Станция " . $this->faker->unique()->numberBetween(1, 9999),
            "external_id" => $this->faker->uuid(),
            "line_id" => (string) $this->faker->numberBetween(1, 99),
            "geo_lat" => $this->faker->randomFloat(7, -90, 90),
            "geo_lon" => $this->faker->randomFloat(7, -180, 180),
            "is_closed" => $this->faker->boolean(10),
            "city_id" => City::factory(),
            "metro_line_id" => function (array $attributes): string {
                return (string) MetroLine::factory()->create(["city_id" => $attributes["city_id"]])
                    ->id;
            },
            "source" => $this->faker->randomElement(["manual", "dadata"]),
        ];
    }
}
