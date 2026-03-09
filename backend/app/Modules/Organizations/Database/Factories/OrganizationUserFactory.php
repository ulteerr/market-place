<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationUser;
use Modules\Users\Models\User;

/**
 * @extends Factory<OrganizationUser>
 */
final class OrganizationUserFactory extends Factory
{
    protected $model = OrganizationUser::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "user_id" => User::factory(),
            "position" => $this->faker->randomElement(["Тренер", "Администратор", "Координатор"]),
            "status" => $this->faker->randomElement(["active", "pending", "blocked"]),
            "invited_by_user_id" => null,
            "joined_at" => $this->faker->optional()->dateTimeBetween("-2 years", "now"),
        ];
    }
}
