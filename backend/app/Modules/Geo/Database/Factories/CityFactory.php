<?php

declare(strict_types=1);

namespace Modules\Geo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

/**
 * @extends Factory<City>
 */
final class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            "name" => $this->faker->unique()->city(),
            "region_id" => Region::factory(),
            "country_id" => null,
        ];
    }
}
