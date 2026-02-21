<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\OrganizationRole;

/**
 * @extends Factory<OrganizationRole>
 */
final class OrganizationRoleFactory extends Factory
{
    protected $model = OrganizationRole::class;

    public function definition(): array
    {
        return [
            "code" => "role_" . $this->faker->unique()->lexify("??????"),
        ];
    }
}
