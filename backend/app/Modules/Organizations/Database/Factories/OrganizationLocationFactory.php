<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;

/**
 * @extends Factory<OrganizationLocation>
 */
final class OrganizationLocationFactory extends Factory
{
    protected $model = OrganizationLocation::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "country_id" => null,
            "region_id" => null,
            "city_id" => null,
            "district_id" => null,
            "address" => $this->faker->optional()->address(),
            "lat" => $this->faker->optional()->latitude(),
            "lng" => $this->faker->optional()->longitude(),
        ];
    }
}
