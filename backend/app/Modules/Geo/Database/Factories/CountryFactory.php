<?php

declare(strict_types=1);

namespace Modules\Geo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Geo\Models\Country;

/**
 * @extends Factory<Country>
 */
final class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            "name" => $this->faker->unique()->country(),
            "iso_code" => strtoupper($this->faker->unique()->lexify("???")),
        ];
    }
}
