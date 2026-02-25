<?php

declare(strict_types=1);

namespace Modules\Geo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\Region;

/**
 * @extends Factory<Region>
 */
final class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            "name" => "Region " . $this->faker->unique()->city(),
            "country_id" => Country::factory(),
        ];
    }
}
