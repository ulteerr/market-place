<?php

declare(strict_types=1);

namespace Modules\Metro\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Geo\Models\City;
use Modules\Metro\Models\MetroLine;

/**
 * @extends Factory<MetroLine>
 */
final class MetroLineFactory extends Factory
{
    protected $model = MetroLine::class;

    public function definition(): array
    {
        return [
            "name" => "Линия " . $this->faker->unique()->numberBetween(1, 9999),
            "external_id" => $this->faker->uuid(),
            "line_id" => (string) $this->faker->numberBetween(1, 99),
            "color" => strtoupper($this->faker->hexColor()),
            "city_id" => City::factory(),
            "source" => $this->faker->randomElement(["manual", "dadata"]),
        ];
    }
}
