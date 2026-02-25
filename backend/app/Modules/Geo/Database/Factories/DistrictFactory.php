<?php

declare(strict_types=1);

namespace Modules\Geo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Geo\Models\City;
use Modules\Geo\Models\District;

/**
 * @extends Factory<District>
 */
final class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            "name" => "District " . $this->faker->unique()->streetName(),
            "city_id" => City::factory(),
        ];
    }
}
