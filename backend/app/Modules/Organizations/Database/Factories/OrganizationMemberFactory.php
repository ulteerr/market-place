<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Users\Models\User;

/**
 * @extends Factory<OrganizationMember>
 */
final class OrganizationMemberFactory extends Factory
{
    protected $model = OrganizationMember::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "user_id" => User::factory(),
            "role_id" => OrganizationRole::factory(),
            "role_code" => $this->faker->randomElement(["owner", "admin", "manager", "member"]),
            "status" => $this->faker->randomElement(["active", "pending", "blocked"]),
            "invited_by_user_id" => null,
            "joined_at" => $this->faker->optional()->dateTimeBetween("-2 years", "now"),
        ];
    }
}
